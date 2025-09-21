from sqlalchemy.orm import Session
import models, schemas


def create_review(db: Session, review: schemas.ReviewCreate):
    db_review = models.Review(**review.dict())
    db.add(db_review)
    db.commit()
    db.refresh(db_review)
    return db_review


def get_reviews_by_tradie_id(db: Session, tradie_id: int):
    return (
        db.query(models.Review)
        .filter(models.Review.tradie_account_id == tradie_id)
        .all()
    )
