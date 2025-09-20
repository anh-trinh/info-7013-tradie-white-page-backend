const express = require('express');
const mysql = require('mysql2/promise');
const jwt = require('jsonwebtoken');
const app = express();
app.use(express.json());
const PORT = process.env.PORT || 3001;
const DB_HOST = process.env.DB_HOST || 'mysql';
const DB_USER = process.env.DB_USER || 'homestead';
const DB_PASS = process.env.DB_PASS || 'secret';
const DB_NAME = process.env.DB_NAME || 'review_db';
const JWT_SECRET = process.env.JWT_SECRET || 'booking_jwt_secret';
const pool = mysql.createPool({ host: DB_HOST, user: DB_USER, password: DB_PASS, database: DB_NAME, waitForConnections: true, connectionLimit: 10 });
function authenticate(req, res, next) {
  const authHeader = req.headers['authorization'];
  if (!authHeader) return res.status(401).json({ error: 'Unauthorized' });
  const token = authHeader.split(' ')[1];
  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) return res.status(401).json({ error: 'Unauthorized' });
    req.user = user;
    next();
  });
}
app.post('/api/reviews', authenticate, async (req, res) => {
  const { booking_id, tradie_account_id, rating, comment } = req.body;
  const resident_account_id = req.user?.sub || req.user?.id || null;
  if (!booking_id || !tradie_account_id || !rating) return res.status(400).json({ error: 'Required fields missing' });
  const [result] = await pool.query("INSERT INTO reviews (booking_id, resident_account_id, tradie_account_id, rating, comment) VALUES (?,?,?,?,?)", [booking_id, resident_account_id, tradie_account_id, rating, comment]);
  const [rows] = await pool.query("SELECT * FROM reviews WHERE id=?", [result.insertId]);
  res.status(201).json(rows[0]);
});
app.get('/api/reviews/tradie/:tradieId', async (req, res) => {
  const [rows] = await pool.query("SELECT * FROM reviews WHERE tradie_account_id=?", [req.params.tradieId]);
  res.json(rows);
});
app.get('/api/reviews/booking/:bookingId', async (req, res) => {
  const [rows] = await pool.query("SELECT * FROM reviews WHERE booking_id=?", [req.params.bookingId]);
  res.json(rows[0] || {});
});
app.get('/api/reviews/tradie/:tradieId/average', async (req, res) => {
  const [rows] = await pool.query("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE tradie_account_id=?", [req.params.tradieId]);
  res.json(rows[0]);
});
app.get('/health', (req, res) => res.json({ ok: true }));
app.listen(PORT, () => console.log(`Review service running on port ${PORT}`));
