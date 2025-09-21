FROM python:3.10-slim

WORKDIR /app

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

# System deps for building wheels (uvloop, httptools, cryptography) and MySQL client
RUN apt-get update \
 && apt-get install -y --no-install-recommends build-essential python3-dev \
    libssl-dev libffi-dev cargo \
    default-libmysqlclient-dev pkg-config \
 && rm -rf /var/lib/apt/lists/*

COPY requirements.txt ./
RUN pip install --upgrade pip \
 && pip install --no-cache-dir -r requirements.txt

COPY ./app /app

EXPOSE 8000

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
