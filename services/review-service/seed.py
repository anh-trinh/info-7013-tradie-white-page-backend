from faker import Faker
from database import SessionLocal
from models import Review

def main() -> None:
    fake = Faker()
    db = SessionLocal()
    try:
        for _ in range(10):
            review = Review(
                booking_id=fake.unique.random_int(min=1, max=100),
                resident_account_id=fake.random_int(min=1, max=10),
                tradie_account_id=fake.random_int(min=1, max=10),
                rating=fake.random_int(min=3, max=5),
                comment=fake.paragraph(nb_sentences=3),
            )
            db.add(review)
        db.commit()
    finally:
        db.close()

if __name__ == "__main__":
    main()
