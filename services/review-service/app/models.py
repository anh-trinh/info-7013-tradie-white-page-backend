from sqlalchemy import Column, Integer, Text
from database import Base


class Review(Base):
    __tablename__ = "reviews"

    id = Column(Integer, primary_key=True, index=True)
    booking_id = Column(Integer, unique=True, nullable=False)
    resident_account_id = Column(Integer, nullable=False)
    tradie_account_id = Column(Integer, nullable=False)
    rating = Column(Integer, nullable=False)
    comment = Column(Text, nullable=True)
