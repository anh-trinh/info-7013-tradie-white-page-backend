FROM python:3.10-slim

WORKDIR /app

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

# Rely on prebuilt wheels from PyPI to avoid needing apt packages during build
COPY requirements.txt ./
RUN pip install --upgrade pip \
 && pip install --no-cache-dir -r requirements.txt

COPY ./app /app
COPY seed.py /app/seed.py

EXPOSE 8000

# Allow PORT override for Railway; default to 8000 locally
ENV PYTHONPATH=/app
CMD ["sh", "-c", "python /app/seed.py || true; uvicorn main:app --host 0.0.0.0 --port ${PORT:-8000}"]
