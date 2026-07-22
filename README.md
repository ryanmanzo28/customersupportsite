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

- Frontend: http://localhost:5173 (production build served locally)
- Dashboard: http://localhost:5173/app.html
- Backend: http://localhost:8080
- Health: http://localhost:8080/api/health.json
- API: http://localhost:8080/api/tickets.json

## Using the app

1. Open the Login page or Register a new account.
2. Sign in, then open the dashboard at `http://localhost:5173/app.html`.
3. Create a ticket, open it from the conversation list, update its status, and add comments.

The dashboard requires a signed-in account for ticket changes. The ticket list and dashboard summary remain available for browsing.

## Demo Login

- Username: demo
- Password: demo

## Stop

```powershell
docker compose down
```
