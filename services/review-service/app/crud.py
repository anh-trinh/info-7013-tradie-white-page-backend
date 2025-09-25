from sqlalchemy.orm import Session
from sqlalchemy import func
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


def get_review_by_booking_id(db: Session, booking_id: int):
    return db.query(models.Review).filter(models.Review.booking_id == booking_id).first()


def get_reviewed_booking_ids(db: Session, booking_ids: list[int]):
    if not booking_ids:
        return set()
    rows = (
        db.query(models.Review.booking_id)
        .filter(models.Review.booking_id.in_(booking_ids))
        .all()
    )
    return {r[0] for r in rows}


def get_reviews_summary_by_tradie_ids(db: Session, tradie_ids: list[int]):
    if not tradie_ids:
        return []
    rows = (
        db.query(
            models.Review.tradie_account_id.label('tradie_account_id'),
            func.count(models.Review.id).label('reviews_count'),
            func.avg(models.Review.rating).label('average_rating'),
        )
        .filter(models.Review.tradie_account_id.in_(tradie_ids))
        .group_by(models.Review.tradie_account_id)
        .all()
    )
    # Convert to plain dicts with rounded average
    result = []
    for r in rows:
        avg = float(r.average_rating) if r.average_rating is not None else 0.0
        result.append({
            'tradie_account_id': int(r.tradie_account_id),
            'reviews_count': int(r.reviews_count or 0),
            'average_rating': round(avg, 1),
        })
    return result
