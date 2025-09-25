from fastapi import FastAPI, Depends, Header, HTTPException, Query
import os
from typing import List
from sqlalchemy.orm import Session

import crud, models, schemas
from database import SessionLocal, engine

models.Base.metadata.create_all(bind=engine)

app = FastAPI()
def require_admin(x_user_role: str | None = Header(default=None, alias="X-User-Role")):
    if x_user_role != "admin":
        raise HTTPException(status_code=403, detail="Forbidden")
    return True


def require_resident(x_user_role: str | None = Header(default=None, alias="X-User-Role")):
    if x_user_role != "resident":
        raise HTTPException(status_code=403, detail="Only resident can create reviews")
    return True


def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


@app.post("/api/reviews", response_model=schemas.Review, dependencies=[Depends(require_resident)])
def create_review(
    review: schemas.ReviewCreateFlexible,
    db: Session = Depends(get_db),
    x_user_id: int | None = Header(default=None, alias="X-User-Id"),
):
    # Fill resident id from auth header if missing
    resident_id = review.resident_account_id or x_user_id
    if not resident_id:
        raise HTTPException(status_code=422, detail="resident_account_id is required")
    if not review.tradie_account_id:
        raise HTTPException(status_code=422, detail="tradie_account_id (or tradie_id) is required")

    # Ensure one review per booking (unique booking_id)
    existing = crud.get_review_by_booking_id(db, review.booking_id)
    if existing:
        raise HTTPException(status_code=409, detail="Review for this booking already exists")

    strict = schemas.ReviewCreate(
        booking_id=review.booking_id,
        resident_account_id=int(resident_id),
        tradie_account_id=int(review.tradie_account_id),
        rating=review.rating,
        comment=review.comment,
    )
    return crud.create_review(db=db, review=strict)


@app.get("/api/reviews/tradie/{tradie_id}", response_model=list[schemas.Review])
def read_reviews_for_tradie(tradie_id: int, db: Session = Depends(get_db)):
    reviews = crud.get_reviews_by_tradie_id(db, tradie_id=tradie_id)
    if reviews:
        return reviews
    # Fallback: resolve tradie profile id -> account_id via Tradie Service
    try:
        import httpx
        base = os.getenv('TRADIE_SERVICE_URL', 'http://tradie-service:3000').rstrip('/')
        # Use lightweight internal mapping endpoint to avoid heavy enrichment
        r = httpx.get(f"{base}/api/internal/tradies/{tradie_id}", timeout=2.5)
        if r.status_code == 200:
            data = r.json() or {}
            acc_id = data.get('account_id')
            if acc_id and int(acc_id) != int(tradie_id):
                return crud.get_reviews_by_tradie_id(db, tradie_id=int(acc_id))
    except Exception:
        pass
    return []


@app.get("/api/admin/reviews", response_model=list[schemas.Review], dependencies=[Depends(require_admin)])
def read_all_reviews(db: Session = Depends(get_db)):
    return crud.get_all_reviews(db=db)


@app.delete("/api/admin/reviews/{review_id}", status_code=204, dependencies=[Depends(require_admin)])
def delete_review(review_id: int, db: Session = Depends(get_db)):
    crud.delete_review_by_id(db=db, review_id=review_id)
    return {"message": "Review deleted successfully"}


# Internal-only: bulk review status lookup for booking_ids
@app.get("/api/internal/reviews/status", response_model=List[int])
def get_reviews_status(booking_ids: List[int] = Query(None), db: Session = Depends(get_db)):
    if not booking_ids:
        return []
    reviewed_ids = crud.get_reviewed_booking_ids(db, booking_ids=booking_ids)
    return list(reviewed_ids)

# Internal-only: bulk ratings summary by tradie account IDs
@app.get("/api/internal/reviews/summary")
def get_reviews_summary(tradie_ids: List[int] = Query(None), db: Session = Depends(get_db)):
    if not tradie_ids:
        return []
    return crud.get_reviews_summary_by_tradie_ids(db, tradie_ids=tradie_ids)
