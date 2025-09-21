# Tradie White Page – Backend

Production-like, containerized microservices backend with centralized API Gateway, JWT auth, durable databases, and asynchronous messaging (RabbitMQ).

Gateway URL: http://localhost:8888


## Architecture

- API Gateway: NGINX reverse proxy, centralized auth using auth_request (delegated to Account Service).
- Services:
	- account-service (Lumen/PHP): authentication, user profiles, admin account management.
	- booking-service (Lumen/PHP): quotes, bookings, admin job operations.
	- tradie-service (Lumen/PHP): service categories, search, admin category management.
	- review-service (FastAPI/Python): reviews CRUD and admin moderation.
	- notification-service (NestJS/Node): background consumer for domain events via RabbitMQ.
- Datastores: Dedicated MySQL per service with named Docker volumes for persistence.
- Messaging: RabbitMQ (management UI on 15672).

All traffic flows through NGINX at :8888. Protected routes are authenticated centrally before requests reach services.


## Prerequisites

- Docker and Docker Compose
- Ports available on host:
	- 8888 (gateway), 5672/15672 (RabbitMQ), 3307..3308 (some MySQLs), 8001 (review docs)


## Quick start

1) Start backend stack
```bash
docker compose up -d
```

2) Verify services
- Gateway: http://localhost:8888
- RabbitMQ UI: http://localhost:15672 (guest/guest)
- Review API docs (FastAPI): http://localhost:8001/docs

3) Seed or register first user
- Use Accounts API to register and login (see Auth below).

4) Start frontends (on host machine)

- MPA (server-rendered, proxied at /, /search, /tradie, /login, /register, /admin/login)
	- Must run on host port 8000 to match nginx proxy
	- Example (Laravel/Lumen dev server):
		```bash
		php -S 0.0.0.0:8000 -t public
		```

- SPA (Vite/React, proxied at /dashboard, /tradie-dashboard, /admin)
	- Must run on host port 5173 (Vite default)
	- Example:
		```bash
		npm install
		npm run dev
		```
	- Ensure the dev server binds to 0.0.0.0 if needed so NGINX can reach it via host.docker.internal


## Services & Ports

- api-gateway (NGINX): 8888
- account-service (PHP): internal 8000
- booking-service (PHP): internal 80
- tradie-service (PHP): internal 8000
- review-service (Python): 8000 (exposed as host 8001)
- notification-service (NestJS): RMQ microservice, no HTTP port by default
- RabbitMQ: 5672 (AMQP), 15672 (UI)
- MySQL:
	- notification-db: 3307
	- booking-db: 3308
	- review-db: 3309
	- account-db: 3310
	- tradie-db: 3311

Tip: Connect with MySQL Workbench using Host: 127.0.0.1, Port above, User/Password from docker-compose env (e.g., root/root or your_user/your_password), and Database name per service.


## Authentication (Centralized at Gateway)

Flow:
1) Client calls public login: POST /api/accounts/login (no token required).
2) On protected routes, NGINX calls internal /_auth_verify → Account Service validates JWT.
3) If token is valid, NGINX forwards the request to the target service and injects X-User-Id, X-User-Role from the sub-request.

Public endpoints
- POST /api/accounts/login
- POST /api/accounts/register

Protected examples
- /api/accounts/* (except login/register)
- /api/bookings, /api/quotes
- /api/tradies, /api/services
- /api/reviews, /api/admin/reviews
- /api/admin/* (admin-only)

Admin enforcement
- account-service: admin routes guarded by auth + custom admin middleware.
- booking-service & tradie-service: custom AdminMiddleware checks X-User-Role == admin.
- review-service: FastAPI dependency requires X-User-Role == admin.


## Messaging (RabbitMQ)

- Exchange: default (empty), Queue: notifications_queue (durable)
- Publishers:
	- account-service: account_registered { email, first_name }
	- booking-service: booking_created { booking_id }, job_completed { booking_id }
	- review-service: review_submitted { review_id, rating }
- Consumer:
	- notification-service: NestJS RMQ microservice with handlers for above patterns

Implementation notes
- Lumen publishers send body with fields: { pattern, data }
- FastAPI publisher sends same shape for interoperability.
- Notification service listens with @EventPattern('<pattern>').


## Persistence

Each MySQL service mounts a named Docker volume at /var/lib/mysql. Volumes are declared at the bottom of docker-compose.yml (e.g., booking-db-data). Data survives container restarts.


## Basic smoke tests

Get a token
```bash
curl -sS -X POST http://localhost:8888/api/accounts/login \
	-H 'Content-Type: application/json' \
	-d '{"email":"admin@example.com","password":"your_password"}'
```
Set a shell variable TOKEN from response.

Protected without token → 401
```bash
curl -i http://localhost:8888/api/bookings
```

Protected with token → 200
```bash
curl -i http://localhost:8888/api/bookings -H "Authorization: Bearer $TOKEN"
```

Admin endpoints (examples)
```bash
curl -sS http://localhost:8888/api/admin/accounts -H "Authorization: Bearer $TOKEN"
curl -sS http://localhost:8888/api/admin/bookings -H "Authorization: Bearer $TOKEN"
curl -sS http://localhost:8888/api/admin/categories -H "Authorization: Bearer $TOKEN"
curl -sS http://localhost:8888/api/admin/reviews -H "Authorization: Bearer $TOKEN"
```

Review docs (Swagger)
```bash
open http://localhost:8001/docs
```

Messaging quick check
- Trigger an action (register user, create booking, submit review)
- Watch notification-service logs for lines starting with `EVENT:`


## Troubleshooting

- Ports in use: stop any process occupying 8888/5672/15672/8001.
- Slow first run: Docker will pull images and build; subsequent runs are faster.
- DB credentials: keep compose service env in sync with app configs.
- RabbitMQ not receiving: ensure queue `notifications_queue` exists and durable=true; check publisher logs.
- 401 on protected routes: verify token is sent in Authorization header and not expired.
- 403 on admin routes: ensure the account has role=admin.


## Development tips

- To add a new admin-only endpoint in PHP services, reuse AdminMiddleware.
- To enforce admin in FastAPI, reuse the `require_admin` dependency.
- For new async events, publish with shape `{ pattern, data }` and add a matching `@EventPattern` handler in notification-service.


Happy building! 🚀
