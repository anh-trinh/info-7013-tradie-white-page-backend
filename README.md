# Tradie White Page Project

## Quick Start Guide

**Requirements:** Docker and Docker Compose installed on your system.

### Getting Started

1. Clone this repository to your local machine
2. Navigate to the project root directory
3. Run the following command to start the entire system:
   ```bash
   docker compose up -d
   ```
4. The system will take a few minutes to initialize. Once complete:
   * Access the application at: `http://localhost:8888`
   * Access RabbitMQ management UI at: `http://localhost:15672` (username: `guest`, password: `guest`)

### System Architecture

Production-ready microservices backend with:

- **API Gateway**: NGINX reverse proxy with centralized JWT authentication
- **Core Services**:
  - `account-service` (PHP/Lumen): User authentication and profiles
  - `booking-service` (PHP/Lumen): Booking management and quotes  
  - `tradie-service` (PHP/Lumen): Service categories and search
  - `review-service` (Python/FastAPI): Review system with moderation
  - `notification-service` (Node.js/NestJS): Background event processing
- **Data Layer**: Dedicated MySQL databases per service with persistent volumes
- **Messaging**: RabbitMQ for asynchronous communication

All API requests flow through port `8888`. Protected endpoints require JWT authentication.

## Prerequisites

- Docker and Docker Compose
- Available ports: 8888 (gateway), 5672/15672 (RabbitMQ), 3307-3311 (databases), 8001 (API docs)


## Quick start

1) Start backend stack
```bash
docker compose up -d --build
```

2) Verify services
- Gateway: http://localhost:8888
- RabbitMQ UI: http://localhost:15672 (guest/guest)

## Development Setup

1. **Start the backend services:**
   ```bash
   docker compose up -d
   ```

2. **Verify services are running:**
   - Main application: http://localhost:8888
   - RabbitMQ Management: http://localhost:15672 (guest/guest)
   - Review API Documentation: http://localhost:8001/docs

3. **Create first user account:**
   Use the Account API to register and login (see Authentication section below)

4. **Frontend Development** (if applicable):
   - MPA Frontend: Should run on port 8000 and be accessible via gateway
   - SPA Frontend: Should run on port 5173 (Vite default) and be accessible via gateway

## Service Ports

**External Access:**
- **API Gateway**: `8888` (main entry point)
- **RabbitMQ UI**: `15672` 
- **Review API Docs**: `8001`

**Database Ports** (for direct access):
- notification-db: `3307`
- booking-db: `3308`  
- review-db: `3309`
- account-db: `3310`
- tradie-db: `3311`

*Database credentials: username `root`, password `root`*

**Internal Services** (accessed via gateway):
- account-service, booking-service, tradie-service, review-service
- notification-service (RabbitMQ consumer only)

## Authentication

The system uses centralized JWT authentication via the API Gateway:

1. **Public endpoints** (no authentication required):
   - `POST /api/accounts/login`
   - `POST /api/accounts/register`

2. **Protected endpoints** (JWT token required):
   - `/api/accounts/*` (except login/register)
   - `/api/bookings`, `/api/quotes`
   - `/api/tradies`, `/api/services`
   - `/api/reviews`

3. **Admin endpoints** (admin role required):
   - `/api/admin/*`
   - `/api/admin/reviews`

**Authentication Flow:**
1. Client calls `POST /api/accounts/login` to get JWT token
2. For protected routes, include token in `Authorization: Bearer <token>` header
3. NGINX gateway validates token with account-service before forwarding requests
4. Valid requests include user context (`X-User-Id`, `X-User-Role`) for downstream services

## Background Processing

The system uses **RabbitMQ** for asynchronous communication:

- **Publishers**: account-service, booking-service, review-service
- **Consumer**: notification-service
- **Events**: user registration, booking creation, job completion, review submission
- **Management UI**: http://localhost:15672 (guest/guest)

## Data Persistence

Each service uses a dedicated MySQL database with persistent Docker volumes:
- Data survives container restarts and updates
- Direct database access available on ports 3307-3311
- Credentials: `root/root`

## API Testing

**Quick test - Register a user:**
```bash
curl -X POST http://localhost:8888/api/accounts/register \
  -H 'Content-Type: application/json' \
  -d '{"first_name":"John","last_name":"Doe","email":"john@example.com","password":"password123","role":"tradie"}'
```

**Login to get JWT token:**
```bash
curl -X POST http://localhost:8888/api/accounts/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"john@example.com","password":"password123"}'
```

2) Login and capture tokens
```bash
TRADIE_TOKEN=$(curl -sS -X POST http://localhost:8888/api/accounts/login \
## Troubleshooting

**Common Issues:**

1. **Port conflicts**: Ensure ports 8888, 5672, 15672, 3307-3311, 8001 are available
2. **Services not starting**: Check logs with `docker compose logs <service-name>`
3. **Authentication errors**: Verify JWT token is included in Authorization header
4. **Database connection issues**: Ensure MySQL containers are fully started before services

**Useful Commands:**
```bash
# View all service logs
docker compose logs

# Restart a specific service
docker compose restart <service-name>

# View service status
docker compose ps

# Stop all services
docker compose down

# Start with fresh databases (removes all data)
docker compose down -v && docker compose up -d
```

## Development Notes

- **Default Admin Account**: username `admin`, password `admin`
- **JWT Secret**: Automatically configured via environment variables
- **Database Schema**: Auto-migrated on service startup
- **Hot Reload**: Backend services restart automatically on code changes (if volumes are mounted)
- **API Documentation**: FastAPI services provide interactive docs at `/docs` endpoints

## License

This project is for educational purposes.
```

Admin quick checks
```bash
# promote tradie to admin (inside db container)
docker compose exec account-db mysql -uroot -proot -e "UPDATE account_db.accounts SET role='admin' WHERE email='mike.tradie@test.com'"

ADMIN_TOKEN=$(curl -sS -X POST http://localhost:8888/api/accounts/login \
	-H 'Content-Type: application/json' \
	-d '{"email":"mike.tradie@test.com","password":"password123"}' | jq -r .token)

curl -sS http://localhost:8888/api/admin/accounts -H "Authorization: Bearer $ADMIN_TOKEN"
curl -sS http://localhost:8888/api/admin/bookings -H "Authorization: Bearer $ADMIN_TOKEN"
curl -sS http://localhost:8888/api/admin/categories -H "Authorization: Bearer $ADMIN_TOKEN"
curl -sS http://localhost:8888/api/admin/reviews -H "Authorization: Bearer $ADMIN_TOKEN"
```


## Troubleshooting

- Ports in use: stop any process occupying 8888/5672/15672/8001.
- Slow first run: Docker will pull images and build; subsequent runs are faster.
- DB credentials: keep compose service env in sync with app configs.
- RabbitMQ not receiving: ensure queue `notifications_queue` exists and durable=true; check publisher logs.
- 401 on protected routes: verify token is sent in Authorization header and not expired. The gateway forwards Authorization to account-service for its protected routes when needed.
- 403 on admin routes: ensure the account has role=admin.
- 404 on /api/accounts/* through gateway: ensure the gateway proxies with full request URI. In `nginx/nginx.conf`, accounts block should be `proxy_pass http://account-service:8000;` (no path suffix). Services run with PHP router script (`public/index.php`).


## Development tips

- To add a new admin-only endpoint in PHP services, reuse AdminMiddleware.
- To enforce admin in FastAPI, reuse the `require_admin` dependency.
- For new async events, publish with shape `{ pattern, data }` and add a matching `@EventPattern` handler in notification-service.


Happy building! 🚀
