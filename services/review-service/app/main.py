from fastapi import FastAPI, Depends, Header, HTTPException
from sqlalchemy.orm import Session

import crud, models, schemas
from database import SessionLocal, engine

models.Base.metadata.create_all(bind=engine)

app = FastAPI()
def require_admin(x_user_role: str | None = Header(default=None, alias="X-User-Role")):
    if x_user_role != "admin":
        raise HTTPException(status_code=403, detail="Forbidden")
    return True


def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


@app.post("/api/reviews", response_model=schemas.Review)
def create_review(review: schemas.ReviewCreate, db: Session = Depends(get_db)):
    return crud.create_review(db=db, review=review)


@app.get("/api/reviews/tradie/{tradie_id}", response_model=list[schemas.Review])
def read_reviews_for_tradie(tradie_id: int, db: Session = Depends(get_db)):
    return crud.get_reviews_by_tradie_id(db, tradie_id=tradie_id)


@app.get("/api/admin/reviews", response_model=list[schemas.Review], dependencies=[Depends(require_admin)])
def read_all_reviews(db: Session = Depends(get_db)):
    return crud.get_all_reviews(db=db)


@app.delete("/api/admin/reviews/{review_id}", status_code=204, dependencies=[Depends(require_admin)])
def delete_review(review_id: int, db: Session = Depends(get_db)):
    crud.delete_review_by_id(db=db, review_id=review_id)
    return {"message": "Review deleted successfully"}
