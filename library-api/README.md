# Library Management System – API

REST JSON API for the Library Management System take-home assignment.

| Layer    | Technology                                   |
|----------|----------------------------------------------|
| Backend  | Laminas MVC 3 (PHP 8.1 – 8.3)                |
| ORM      | Doctrine ORM 2.20 + Doctrine Migrations 3    |
| Database | PostgreSQL (tested on 16 and 17)             |
| Auth     | JWT bearer tokens (`firebase/php-jwt`, HS256) |
| Frontend | React + Bootstrap 5 / SCSS (`../library-frontend`) |

---

## 1. Setup (Windows / PowerShell)

Requirements: PHP 8.3 with `pdo_pgsql` enabled, Composer, PostgreSQL.

```powershell
# 1. PHP extension check (must print pdo_pgsql)
php -m | findstr pgsql

# 2. Database + user (once)
& "C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -c "CREATE USER library_user WITH PASSWORD 'library_pass';"
& "C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -c "CREATE DATABASE library_db OWNER library_user;"
& "C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -c "CREATE DATABASE library_test OWNER library_user;"   # for integration tests

# 3. Dependencies + autoloader
composer install

# 4. Local config (git-ignored) – only if the files don't exist yet
Copy-Item config\autoload\doctrine.local.php.dist config\autoload\doctrine.local.php   # DB user / password
Copy-Item config\autoload\library.local.php.dist  config\autoload\library.local.php    # then set a random JWT secret:
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"

# 5. Schema (Doctrine migrations) + first Super Admin + demo data
php vendor\bin\doctrine-module migrations:migrate --no-interaction
php vendor\bin\laminas library:seed --demo

# 6. Run
composer serve            # http://127.0.0.1:8080/api/health
```

Demo accounts created by `library:seed --demo`:

| Role        | Email                      | Password       |
|-------------|----------------------------|----------------|
| Super Admin | ardian@admin.com           | `Admin123!`    |
| Operator    | olta@operator.com          | `Operator123!` |
| Student     | artabajrami@student.com    | `Student123!`  |
| Student     | gentianaidrizi@student.com | `Student123!`  |

Without `--demo` only the Super Admin is created (`--admin-email`, `--admin-password` to change it).

### Useful commands

```powershell
php vendor\bin\doctrine-module orm:validate-schema          # entities == database?
php vendor\bin\doctrine-module migrations:status
php vendor\bin\doctrine-module migrations:diff              # after changing an entity
composer test                                               # PHPUnit (unit + integration)
composer clear-config-cache                                 # if config changes don't show up
```

---

## 2. Authentication – JWT (why)

`POST /api/auth/login {email, password}` returns `{ token, expiresAt, user }`. Every other
call sends `Authorization: Bearer <token>`.

**Why tokens instead of sessions:** the React app is a separate SPA on another origin
(`localhost:5173` in development). A bearer token avoids cross-origin cookies, CSRF
protection and server-side session storage, and keeps the API stateless.
**Trade-offs:** a token cannot be revoked before it expires (TTL 8 h). To limit this, the
user is re-loaded from the database on every request, so a deleted account or a changed
role takes effect immediately. The frontend keeps the token in memory/`localStorage`;
an httpOnly refresh-cookie would be the next hardening step.

Passwords are hashed with bcrypt (`password_hash`, cost 12) and never logged.

---

## 3. API overview

All responses are JSON. Errors always look like:

```json
{ "error": { "code": "BOOK_UNAVAILABLE", "message": "\"1984\" is currently unavailable: all copies are borrowed.", "details": { "status": "unavailable" } } }
```

| Method | URL | Who | Purpose |
|---|---|---|---|
| GET  | `/api/health` | public | PHP + DB check |
| POST | `/api/auth/login` | public | Get a token |
| GET  | `/api/auth/me` | all | Current user (+ unpaid total for students) |
| GET  | `/api/books?q=&category=&available=1&page=&limit=` | all | Search books, see availability |
| GET  | `/api/books/categories` | all | Category list |
| GET  | `/api/books/:id` | all | One book |
| POST / PUT / PATCH / DELETE | `/api/books[/:id]` | operator, admin | Manage inventory |
| POST | `/api/loans` `{bookId}` | student | **Borrow** (fee charged, due date set) |
| GET  | `/api/loans?status=open\|active\|overdue\|awaiting_payment\|closed&userId=` | all | Students: own loans / history. Staff: all |
| GET  | `/api/loans/:id` | owner, staff | One loan |
| POST | `/api/loans/:id/return` | owner, staff | **Return** (penalty if late) |
| GET  | `/api/penalties?paid=0\|1&userId=` | all | Students: own. Staff: all |
| POST | `/api/penalties/:id/pay` | owner, staff | **Pay** (staff = on student's behalf) |
| GET  | `/api/users?role=&q=` , `/api/users/:id` | operator (students only), admin | Accounts |
| GET  | `/api/users/:id/history` | operator, admin | Student's loans + penalty status |
| POST / PUT / PATCH / DELETE | `/api/users[/:id]` | admin | Manage Student/Operator accounts |
| GET  | `/api/settings` | all | Fee, rate, period, limit, low-stock threshold |
| PUT / PATCH | `/api/settings` | admin | Change settings |
| GET  | `/api/logs?actionType=&entityType=&actorId=&from=&to=` | admin | System log |
| GET  | `/api/notifications?unread=1` | all | In-app alerts |
| POST | `/api/notifications/:id/read`, `/api/notifications/read-all` | all | Mark read |

---

## 4. Business rules and where they live

| Rule | Code |
|---|---|
| Unpaid-penalty lockout, borrowing limit, stock check | `Service/BorrowPolicy.php` (unit tested) |
| Days late & amount (`days_late × rate`, starts the day **after** the due date) | `Service/PenaltyCalculator.php` |
| Borrow & return transactions, row locks | `Service/LoanService.php` |
| Pay penalty → loan `closed` | `Service/PenaltyService.php`, `Entity/Penalty::markPaid()` |
| Stock never < 0, status derived from stock | `Entity/Book.php` + DB `CHECK (stock >= 0)` |
| Settings stored in `system_settings`, editable by Super Admin only | `Service/SettingsService.php`, migration `Version20261119130600` |
| Every borrow/return/penalty/inventory/account/settings change is logged | `Service/AuditLogger.php` → `system_logs` |
| Notifications (low/out of stock → staff; overdue/penalty → student) | `Service/NotificationService.php` |

Loan status flow: `active → overdue → (returned) → awaiting_payment → (paid) → closed`,
or `active/overdue → (returned on time) → closed`.

**Concurrency:** borrow and return run in one transaction with `SELECT … FOR UPDATE` on
the book and on the student, so two students can't both get the last copy, and one
student can't exceed the limit with parallel requests (verified with 6 simultaneous
requests for a book with stock 1 → exactly one loan, stock 0).

---

## 5. Assumptions and trade-offs

1. **Payments are simulated.** "Pays a borrowing fee" is recorded on the loan
   (`borrow_fee`, a snapshot of the setting at checkout); there is no payment gateway.
   Penalty payment is a single action that marks it paid.
2. **Only an unpaid penalty blocks borrowing**, exactly as the brief says. A penalty is created
   when the book is returned late, so a book that is overdue but not yet returned does not block
   borrowing by itself; the student is warned about it in notifications and on "My books".
3. **Returning on the due date is on time.** 3 days late = €3 with the default rate.
   Dates are compared as calendar days in the library time zone (`Europe/Belgrade`,
   see `module/Library/config/module.config.php`).
4. **Who can return:** the brief has "Student initiates return" and "Operator processes
   returns", so both are allowed (students only their own loans).
5. **Soft delete** for books and users (`deleted_at`) so loan history, penalties and logs
   keep valid references. A book with borrowed copies, or a user with open loans / unpaid
   penalties, can't be deleted. Re-adding a deleted book's ISBN restores that record.
6. **Super Admin accounts** are created only by the seed command; through the API the
   Super Admin manages Student and Operator accounts (as the brief states).
7. **Low-stock threshold** is a setting too (default `stock ≤ 2`). A staff notification is
   created on every stock change that lands at/below the threshold or at 0.
8. **Student alerts** (overdue / pending penalty) are generated when the student opens
   their notifications, each alert once (dedupe key). A scheduled job could do the same
   overnight; not needed for an in-app dashboard.
9. Settings validation: fee/rate ≥ 0 with 2 decimals, period 1–365 days, limit 1–50.

---

## 6. Tests

```powershell
composer test
```

* `test/Service` – penalty calculation (on-time boundary, 1 day late, month end, DST),
  borrow rules (last copy, out of stock, unpaid-penalty lockout, limit), money maths.
* `test/Entity` – stock/status, loan status transitions, no double return/payment.
* `test/Service/BorrowReturnIntegrationTest` – full borrow → return → penalty → pay flow
  on a real PostgreSQL database (`library_test`, wiped on each test; skipped if it doesn't
  exist). Time is frozen with `FixedClock`, so due-date edge cases are exact.

---

## 7. Project layout

```
module/Library/
  config/module.config.php   routes, services, Doctrine mapping, CORS/JWT settings
  src/Command                library:seed console command
  src/Controller             REST controllers (+ AbstractApiController)
  src/Entity                 Doctrine entities + Role / LoanStatus enums
  src/Exception              errors returned as JSON
  src/Factory                service factories
  src/Listener               auth, CORS and JSON error listeners
  src/Repository             database queries
  src/Service                business logic (borrow, return, pay, inventory, users, settings, JWT)
  src/Validator              request validation
  test/                      PHPUnit tests
data/Migrations              Doctrine migrations
```

The API has no server-rendered pages: every URL (including `/` and unknown URLs) answers with JSON.
