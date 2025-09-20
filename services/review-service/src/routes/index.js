const express = require('express');
const router = express.Router();
const reviewController = require('../controllers/review.controller');

router.get('/reviews/tradie/:id', reviewController.getReviewsByTradie);
router.post('/reviews', reviewController.createReview);
router.get('/admin/reviews', reviewController.getAllReviewsForAdmin);
router.delete('/admin/reviews/:id', reviewController.deleteReview);

module.exports = router;
