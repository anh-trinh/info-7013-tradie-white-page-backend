import Fastify from 'fastify';
import fastifyJwt from '@fastify/jwt';
import mysql from 'mysql2/promise';
const FASTIFY_PORT = process.env.PORT || 3001;
const DB_HOST = process.env.DB_HOST || 'mysql';
const DB_USER = process.env.DB_USER || 'homestead';
const DB_PASS = process.env.DB_PASS || 'secret';
const DB_NAME = process.env.DB_NAME || 'review_db';
const JWT_SECRET = process.env.JWT_SECRET || 'booking_jwt_secret';
const fastify = Fastify({ logger: true });
fastify.register(fastifyJwt, { secret: JWT_SECRET });
const pool = mysql.createPool({ host: DB_HOST, user: DB_USER, password: DB_PASS, database: DB_NAME, waitForConnections: true, connectionLimit: 10 });
fastify.decorate("authenticate", async (request, reply) => { try { await request.jwtVerify(); } catch (err) { reply.code(401).send({ error: 'Unauthorized' }); } });
fastify.post('/api/reviews', { preHandler: [fastify.authenticate] }, async (req, reply) => {
  const { booking_id, tradie_account_id, rating, comment } = req.body;
  const resident_account_id = req.user?.sub || req.user?.id || null;
  if (!booking_id || !tradie_account_id || !rating) return reply.code(400).send({ error: 'Required fields missing' });
  const [res] = await pool.query("INSERT INTO reviews (booking_id, resident_account_id, tradie_account_id, rating, comment) VALUES (?,?,?,?,?)",
    [booking_id, resident_account_id, tradie_account_id, rating, comment]);
  const [rows] = await pool.query("SELECT * FROM reviews WHERE id=?", [res.insertId]);
  reply.code(201).send(rows[0]);
});
fastify.get('/api/reviews/tradie/:tradieId', async (req, reply) => {
  const [rows] = await pool.query("SELECT * FROM reviews WHERE tradie_account_id=?", [req.params.tradieId]);
  reply.send(rows);
});
fastify.get('/api/reviews/booking/:bookingId', async (req, reply) => {
  const [rows] = await pool.query("SELECT * FROM reviews WHERE booking_id=?", [req.params.bookingId]);
  reply.send(rows[0] || {});
});
fastify.get('/api/reviews/tradie/:tradieId/average', async (req, reply) => {
  const [rows] = await pool.query("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE tradie_account_id=?", [req.params.tradieId]);
  reply.send(rows[0]);
});
fastify.get('/health', async () => ({ ok: true }));
fastify.listen({ port: FASTIFY_PORT, host: '0.0.0.0' });