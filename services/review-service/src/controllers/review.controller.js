const mysql = require('mysql2/promise');

const dbConfig = {
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME
};

exports.createReview = async (req, res) => {
    const { booking_id, resident_account_id, tradie_account_id, rating, comment } = req.body;
    try {
        const connection = await mysql.createConnection(dbConfig);
        const sql = 'INSERT INTO reviews (booking_id, resident_account_id, tradie_account_id, rating, comment) VALUES (?, ?, ?, ?, ?)';
        const [result] = await connection.execute(sql, [booking_id, resident_account_id, tradie_account_id, rating, comment]);
        // TODO: Publish "review_submitted" event to Message Broker
        res.status(201).json({ id: result.insertId, message: 'Review created successfully' });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'Error creating review' });
    }
};

exports.getReviewsByTradie = async (req, res) => {
    const tradieId = req.params.id;
    try {
        const connection = await mysql.createConnection(dbConfig);
        const sql = 'SELECT rating, comment, created_at FROM reviews WHERE tradie_account_id = ? ORDER BY created_at DESC';
        const [reviews] = await connection.execute(sql, [tradieId]);
        res.status(200).json(reviews);
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'Error fetching reviews' });
    }
};

exports.getAllReviewsForAdmin = async (req, res) => {
    res.status(501).json({ message: 'Not implemented' });
};

exports.deleteReview = async (req, res) => {
    res.status(501).json({ message: 'Not implemented' });
};
