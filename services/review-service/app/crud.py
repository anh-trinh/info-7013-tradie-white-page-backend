from sqlalchemy.orm import Session
import models, schemas
import rabbitmq_client


def create_review(db: Session, review: schemas.ReviewCreate):
    db_review = models.Review(**review.dict())
    db.add(db_review)
    db.commit()
    db.refresh(db_review)
    # Notify Notification Service (email/logging etc.)
    rabbitmq_client.publish('notifications_queue', {
        'pattern': 'review_submitted',
        'data': {
            'review_id': db_review.id,
            'tradie_account_id': db_review.tradie_account_id,
            'rating': db_review.rating,
            'booking_id': db_review.booking_id,
            'resident_account_id': db_review.resident_account_id,
        }
    })

    # Notify Tradie Service worker (to update average_rating, reviews_count)
    rabbitmq_client.publish('rating_update_queue', {
        'tradie_account_id': db_review.tradie_account_id,
        'rating': db_review.rating,
    })
    return db_review


def get_reviews_by_tradie_id(db: Session, tradie_id: int):
    return (
        db.query(models.Review)
        .filter(models.Review.tradie_account_id == tradie_id)
        .all()
    )


def get_all_reviews(db: Session):
    return db.query(models.Review).all()


def delete_review_by_id(db: Session, review_id: int):
    db_review = db.query(models.Review).filter(models.Review.id == review_id).first()
    if db_review:
        db.delete(db_review)
        db.commit()
    return db_review
