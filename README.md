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
psql -U postgres -c "CREATE USER library_user WITH PASSWORD 'library_pass';"
psql -U postgres -c "CREATE DATABASE library_db OWNER library_user;"
psql -U postgres -c "CREATE DATABASE library_test OWNER library_user;"

cd library-api
composer install
composer development-enable
Copy-Item config\autoload\doctrine.local.php.dist config\autoload\doctrine.local.php
(Get-Content config\autoload\library.local.php.dist) -replace 'CHANGE-ME-to-a-random-string-of-at-least-32-characters', (php -r "echo bin2hex(random_bytes(32));") | Set-Content config\autoload\library.local.php
php vendor\bin\doctrine-module migrations:migrate --no-interaction
php vendor\bin\laminas library:seed --demo
composer test
composer serve
```

```powershell
cd library-frontend
npm install
npm run dev
```

Demo logins: `ardian@admin.com` / `Admin123!`, `olta@operator.com` / `Operator123!`,
`artabajrami@student.com` / `Student123!`, `gentianaidrizi@student.com` / `Student123!`.
