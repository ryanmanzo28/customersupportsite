# Customer Support Site

Simple local setup.

## 1) Setup

From the project root, copy env file:

```powershell
Copy-Item .env.example .env
```

## 2) Start

```powershell
./startup.ps1
```

Or:

```powershell
docker compose up -d --build
```

## 3) Open

- Frontend: http://localhost:5173
- Backend: http://localhost:8080
- Health: http://localhost:8080/api/health.json
- API: http://localhost:8080/api/tickets.json

## Demo Login

- Username: demo
- Password: demo

## Stop

```powershell
docker compose down
```