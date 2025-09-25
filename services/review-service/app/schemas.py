from pydantic import BaseModel, Field, AliasChoices
from typing import Optional


class ReviewBase(BaseModel):
    rating: int
    comment: Optional[str] = None


class ReviewCreate(ReviewBase):
    booking_id: int
    resident_account_id: int
    tradie_account_id: int


class ReviewCreateFlexible(ReviewBase):
    booking_id: int
    # Accept either 'tradie_account_id' or legacy/client 'tradie_id'
    tradie_account_id: Optional[int] = Field(default=None, validation_alias=AliasChoices('tradie_account_id', 'tradie_id'))
    # If not provided, we'll derive from X-User-Id header
    resident_account_id: Optional[int] = None


class Review(ReviewBase):
    id: int

    class Config:
        from_attributes = True
