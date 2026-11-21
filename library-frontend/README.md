# Library Management System – Frontend

React 19 + Vite, styled with Bootstrap 5 and SCSS. The app only talks to the Laminas JSON API in `../library-api`.

## Getting started

```powershell
# API
cd library-api
composer serve

# Frontend (second terminal)
cd library-frontend
npm install
npm run dev
```

Open http://localhost:5173. In development Vite proxies `/api` to `http://127.0.0.1:8080` (see `vite.config.js`).
To use a different API host, create `.env.local` with `VITE_API_URL=http://host:port`.

`npm run build` creates a production build in `dist/`.

## Screens

| Role | Screens |
|---|---|
| Student | Find a book, My books, History, Penalties, Notifications |
| Operator | Loans & returns, Inventory, Penalties, Students, Notifications |
| Super Admin | Everything an operator has, plus Accounts, Settings and System log |

## Structure

```
src/
  api/client.js          fetch wrapper for the REST API
  components/            layout, dialogs, tables, shared UI
  context/AuthContext.jsx logged-in user
  hooks/                 useApi, useDebounced
  pages/                 one component per screen
  styles/                SCSS (Bootstrap overrides + partials)
  utils/                 formatting, hash router
  App.jsx, main.jsx
```

The token from `POST /api/auth/login` is stored in `localStorage` and sent as a bearer token. A `401` response logs the user out.
