# AGENTS.md

## Cursor Cloud specific instructions

This is a **Laravel 12 REST API** backend for a POS/store management system ("Management Toko"). All responses are in Indonesian.

### Quick reference

| Task | Command |
|------|---------|
| Install PHP deps | `composer install` |
| Install JS deps | `npm install` |
| Dev server (all services) | `composer dev` |
| PHP server only | `php artisan serve` |
| Run tests | `php artisan test` |
| Lint (check) | `./vendor/bin/pint --test` |
| Lint (fix) | `./vendor/bin/pint` |
| Migrations | `php artisan migrate` |
| Seed data | `php artisan db:seed` |

### Database

The project defaults to MySQL in `.env.example`, but **SQLite works for development and tests**. The `.env` is configured to use SQLite at `database/database.sqlite`. Tests always use SQLite in-memory (configured in `phpunit.xml`).

If the SQLite file is missing, recreate it: `touch database/database.sqlite && php artisan migrate --force`.

### Auth for API testing

Register via `POST /api/v1/auth/register` with fields: `name`, `username`, `email`, `password`. The seeded test user (`test@example.com`) has no known password set via seeder; register a new user instead. Use the returned `access_token` as `Authorization: Bearer <token>`.

### `composer dev` details

Runs four concurrent processes via `npx concurrently`: Laravel server, queue worker, log viewer (Pail), and Vite dev server. For API-only work, `php artisan serve` alone is sufficient.
