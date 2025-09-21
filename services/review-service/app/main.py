from fastapi import FastAPI, Depends
from sqlalchemy.orm import Session

import crud, models, schemas
from database import SessionLocal, engine

models.Base.metadata.create_all(bind=engine)

app = FastAPI()


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
