# Customer Support Site

Monorepo starter with:
- Backend: CakePHP (API-first starter)
- Frontend: Vue 3 + Vite
- Infra: Docker Compose (PHP/Apache + MySQL + Node)

## Structure

- backend: CakePHP backend code and Docker build files
- frontend: Vue app
- docker-compose.yml: local development stack

## Quick Start

1. Create a root .env file from .env.example (single source for backend, frontend, and Docker).

2. From project root, run:

```powershell
docker compose up --build
```

3. Open apps:
- Frontend: http://localhost:5173
- Backend: http://localhost:8080
- API endpoint: http://localhost:8080/api/tickets.json

## Seed Data

The database is initialized on first startup from:
- backend/docker/mysql/initdb.d/001_schema.sql

A demo user is seeded:
- username: demo
- password: demo

## Notes

- Backend dependencies are installed in container startup via Composer.
- Frontend dev server proxies /api requests to the backend service inside Docker.