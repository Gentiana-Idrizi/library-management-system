# Library Management System

Take-home assignment: a library system with three roles (Student, Operator, Super Admin),
book borrowing, returns, penalties, inventory, notifications and system logs.

| Folder | What it is |
|---|---|
| `library-api/` | Backend – Laminas MVC REST API, Doctrine ORM + migrations, PostgreSQL, JWT auth. Setup, API reference, business rules, assumptions and tests are in [`library-api/README.md`](library-api/README.md). |
| `library-frontend/` | Frontend – React (Vite) with Bootstrap 5 / SCSS. Consumes the API only. See [`library-frontend/README.md`](library-frontend/README.md). |

## Quick start (Windows / PowerShell)

Requirements: PHP 8.3 with `pdo_pgsql`, Composer, PostgreSQL, Node.js 20+.

```powershell
# Database (once)
psql -U postgres -c "CREATE USER library_user WITH PASSWORD 'library_pass';"
psql -U postgres -c "CREATE DATABASE library_db OWNER library_user;"
psql -U postgres -c "CREATE DATABASE library_test OWNER library_user;"

# API
cd library-api
composer install
php vendor\bin\doctrine-module migrations:migrate --no-interaction
php vendor\bin\laminas library:seed --demo
composer test
composer serve                      # http://127.0.0.1:8080

# Frontend (second terminal)
cd library-frontend
npm install
npm run dev                         # http://localhost:5173
```

Demo logins: `ardian@admin.com` / `Admin123!`, `olta@operator.com` / `Operator123!`,
`artabajrami@student.com` / `Student123!`, `gentianaidrizi@student.com` / `Student123!`.
