const { Server } = require('socket.io');
const amqp = require('amqplib/callback_api');

// Initialize Socket.IO server on port 4000, synchronized with NGINX and frontend path
const IO_PORT = 4000;
const io = new Server(IO_PORT, {
  cors: { origin: '*' },
  path: '/ws/'
});
console.log(`[Socket.IO] Listening on port ${IO_PORT} with path /ws/`);

const connectedUsers = {}; // { userId: socketId }
const ACCOUNT_SERVICE_URL = process.env.ACCOUNT_SERVICE_URL || 'http://account-service:8000';

io.on('connection', async (socket) => {
  console.log(`[Socket.IO] User connected: ${socket.id}`);

  // Auto register via token in query (?token=...) or Authorization header
  try {
    const q = socket.handshake.query || {};
    const headerAuth = socket.handshake.headers && socket.handshake.headers['authorization'];
    let bearer = '';
    if (typeof q.token === 'string' && q.token) bearer = `Bearer ${q.token}`;
    else if (typeof q.access_token === 'string' && q.access_token) bearer = `Bearer ${q.access_token}`;
    else if (typeof headerAuth === 'string' && headerAuth.toLowerCase().startsWith('bearer ')) bearer = headerAuth;

    if (bearer) {
      const res = await fetch(`${ACCOUNT_SERVICE_URL}/api/accounts/validate`, {
        method: 'GET',
        headers: { Authorization: bearer }
      });
      if (res.ok) {
        const userId = res.headers.get('x-user-id');
        const userRole = res.headers.get('x-user-role');
        if (userId) {
          connectedUsers[userId] = socket.id;
          console.log(`[Socket.IO] Auto-registered user ${userId} (${userRole || 'unknown'}) to socket ${socket.id}`);
        } else {
          console.warn('[Socket.IO] Validate ok but missing X-User-Id header');
        }
      } else {
        console.warn(`[Socket.IO] Token validation failed: ${res.status}`);
      }
    }
  } catch (err) {
    console.error('[Socket.IO] Auto-register failed', err);
  }

  socket.on('register_user', (userId) => {
    console.log(`[Socket.IO] Registering user ${userId} to socket ${socket.id}`);
    connectedUsers[userId] = socket.id;
  });

  socket.on('disconnect', () => {
    // remove from connectedUsers map
    Object.keys(connectedUsers).forEach((uid) => {
      if (connectedUsers[uid] === socket.id) {
        delete connectedUsers[uid];
      }
    });
  });
});

// Listen to Message Broker (resilient retry)
const RABBIT_URL = process.env.RABBITMQ_URL || 'amqp://guest:guest@message-broker:5672';
let amqpConn = null;
function startAmqpConsumer() {
  amqp.connect(RABBIT_URL, function(err, conn) {
    if (err) {
      console.error(`[AMQP] Connection failed: ${err.message}. Retrying in 5s...`);
      setTimeout(startAmqpConsumer, 5000);
      return;
    }
    amqpConn = conn;
    conn.on('error', (e) => {
      console.error('[AMQP] Connection error:', e.message);
    });
    conn.on('close', () => {
      console.warn('[AMQP] Connection closed. Reconnecting in 5s...');
      setTimeout(startAmqpConsumer, 5000);
    });

    conn.createChannel(function(err, ch) {
      if (err) {
        console.error('[AMQP] Channel error:', err.message);
        try { conn.close(); } catch(_) {}
        return;
      }
      const queue = 'realtime_updates_queue';
      ch.assertQueue(queue, { durable: true });
      console.log(`[AMQP] Waiting for messages in ${queue}.`);
      ch.consume(queue, function(msg) {
        try {
          const payload = JSON.parse(msg.content.toString());
          const eventType = payload.pattern || payload.event_type || 'unknown';
          const eventData = payload.data || {};
          console.log(`[AMQP] Received event: ${eventType}`);

        if (eventType === 'new_quote_message') {
          const quote = (eventData && eventData.quote) || {};
          const residentId = quote.resident_account_id;
          const tradieId = quote.tradie_account_id;
          if (residentId && connectedUsers[residentId]) {
            io.to(connectedUsers[residentId]).emit('quote_update', quote);
            console.log(`--> Sent update to Resident ${residentId}`);
          }
          if (tradieId && connectedUsers[tradieId]) {
            io.to(connectedUsers[tradieId]).emit('quote_update', quote);
            console.log(`--> Sent update to Tradie ${tradieId}`);
          }
          return;
        }

        if (eventType === 'new_quote_request') {
          const quote = (eventData && eventData.quote) || {};
          const targetUserId = quote.tradie_account_id;
          if (targetUserId && connectedUsers[targetUserId]) {
            io.to(connectedUsers[targetUserId]).emit('new_quote', quote);
            console.log(`--> Sent event 'new_quote' to Tradie ${targetUserId}`);
          }
          return;
        }

        if (eventType === 'quote_accepted') {
          const quote = (eventData && eventData.quote) || {};
          const residentId = quote.resident_account_id;
          const tradieId = quote.tradie_account_id;
          if (residentId && connectedUsers[residentId]) {
            io.to(connectedUsers[residentId]).emit('quote_status', quote);
            console.log(`--> Sent event 'quote_status' to Resident ${residentId}`);
          }
          if (tradieId && connectedUsers[tradieId]) {
            io.to(connectedUsers[tradieId]).emit('quote_status', quote);
            console.log(`--> Sent event 'quote_status' to Tradie ${tradieId}`);
          }
          return;
        }

        if (eventType === 'booking_created') {
          const booking = (eventData && eventData.booking) || {};
          const quote = booking.quote || {};
          const residentId = quote.resident_account_id;
          const tradieId = quote.tradie_account_id;
          if (residentId && connectedUsers[residentId]) {
            io.to(connectedUsers[residentId]).emit('booking_created', booking);
            console.log(`--> Sent event 'booking_created' to Resident ${residentId}`);
          }
          if (tradieId && connectedUsers[tradieId]) {
            io.to(connectedUsers[tradieId]).emit('booking_created', booking);
            console.log(`--> Sent event 'booking_created' to Tradie ${tradieId}`);
          }
          return;
        }
        } catch (e) {
          console.error('Error handling message', e);
        }
      }, { noAck: true });
    });
  });
}

startAmqpConsumer();
