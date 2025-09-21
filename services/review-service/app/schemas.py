from pydantic import BaseModel
from typing import Optional


class ReviewBase(BaseModel):
    rating: int
    comment: Optional[str] = None


class ReviewCreate(ReviewBase):
    booking_id: int
    resident_account_id: int
    tradie_account_id: int


class Review(ReviewBase):
    id: int

    class Config:
        from_attributes = True
