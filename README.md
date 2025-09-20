# Tradie White Page - Local Development Guide

## Overview

This project uses a microservices architecture for the backend (managed by Docker Compose) and has two frontends:

Everything is unified via the Nginx gateway at http://localhost:8888 so everyone accesses the system the same way on any machine.


## Startup Instructions

### 1. Start the MPA (traditional frontend)

```bash
# Move to the MPA folder
cd path/to/your-mpa-folder
# Start the MPA server (example with Laravel/Lumen)
php -S 0.0.0.0:8000 -t public
```

> Make sure the MPA is running on port 8000.

### 2. Start the SPA (modern frontend)

```bash
# Move to the SPA folder
cd path/to/your-spa-folder
# Install dependencies if needed
npm install
# Start the dev server
npm run dev
```

> The SPA will run on port 5173 (default for Vite).

### 3. Start the backend (microservices & gateway)

```bash
# Move to the backend folder (where docker-compose.yml is located)
cd path/to/info-7013-tradie-white-page-backend
# Start all backend services and the gateway
docker compose up -d
```

> The backend will automatically start all services, databases, and the Nginx gateway.


## Accessing the System

After completing the steps above, everyone can access:

```
http://localhost:8888
```



## Notes


## Contact & Support
If you have issues setting up, contact the team or review the steps above.


Happy coding!
