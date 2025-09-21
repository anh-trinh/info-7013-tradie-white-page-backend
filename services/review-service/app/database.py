import os
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker, declarative_base
from dotenv import load_dotenv

# Load .env in local/dev; on Railway, platform env vars take precedence
load_dotenv()

# Prefer explicit SQLALCHEMY_DATABASE_URL, fallback to DATABASE_URL (Railway default)
db_url = os.getenv("SQLALCHEMY_DATABASE_URL") or os.getenv("DATABASE_URL")

if not db_url:
	raise RuntimeError(
		"DATABASE_URL/SQLALCHEMY_DATABASE_URL is not set. "
		"Provide a valid connection string, e.g. mysql+pymysql://user:pass@host:3306/db"
	)

# Normalize MySQL URL: ensure we use the PyMySQL driver instead of MySQLdb
if db_url.startswith("mysql://"):
	db_url = db_url.replace("mysql://", "mysql+pymysql://", 1)

# Create SQLAlchemy engine and session factory
engine = create_engine(db_url, pool_pre_ping=True, future=True)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()
