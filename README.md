# Finance Tracker API

[![CI](https://github.com/matsvincenzi/finance-tracker-api/actions/workflows/ci.yml/badge.svg)](https://github.com/matsvincenzi/finance-tracker-api/actions/workflows/ci.yml)

A REST API for personal finances: accounts, income and expense categories, and transactions in
**any of ~30 currencies**, converted to Brazilian reais with the **exchange rate of the day the money
moved**, fetched from an external API ([Frankfurter](https://frankfurter.dev), European Central Bank
reference rates). Balances and monthly reports are always in BRL.

Built with **Laravel 13** as a backend portfolio project. It is small on purpose, but treats the
details that matter in real money-handling code seriously: exact arithmetic, ownership checks,
validation at the boundary, resilient integration with a third-party service, and reproducible
environments.

```http
POST /api/v1/transactions
{ "account_id": 2, "category_id": 8, "description": "Cloud hosting",
  "amount": "19.99", "currency": "USD", "occurred_on": "2026-10-11" }
```

```json
{
  "data": {
    "id": 16,
    "type": "expense",
    "description": "Cloud hosting",
    "amount": "19.99",
    "currency": "USD",
    "exchange_rate": "5.005000",
    "amount_brl": "100.05",
    "occurred_on": "2026-10-11",
    "account": { "id": 2, "name": "Credit card" },
    "category": { "id": 8, "name": "Subscriptions", "color": "#6366F1" }
  }
}
```

## Highlights

- **Complete CRUD** for accounts, categories and transactions, with
  [Form Requests](app/Http/Requests) for validation and [API Resources](app/Http/Resources) for a
  stable response format.
- **External API integration** behind an interface
  ([`ExchangeRateProvider`](app/Services/ExchangeRates/ExchangeRateProvider.php)), with timeouts,
  retries only on transient failures, caching by date and a clean `503` + `Retry-After` when the
  provider is down. BRL transactions never depend on it. See [ADR 0002](docs/adr/0002-exchange-rates-from-frankfurter.md).
- **Exact money handling**: amounts are stored as integer cents and exposed as decimal strings; the
  conversion uses PHP 8.4's `BcMath\Number`. No float ever touches a balance.
  See [ADR 0001](docs/adr/0001-money-as-integer-cents.md).
- **Authentication and authorization**: Sanctum bearer tokens that expire after 7 days, rate-limited
  login, and a policy that answers `404` for other users' records, so IDs cannot be probed. It runs
  before validation thanks to Laravel 13's `#[Authorize]` attribute.
- **Filters, sorting and pagination** on transactions; balances computed in SQL with a correlated
  subquery (no N+1, enforced by `Model::shouldBeStrict()` outside production).
- **Monthly report** with income, expenses, balance and a per-category breakdown with percentages.
- **Interactive documentation** generated from the code (OpenAPI 3.1 via Scramble) at `/docs/api`.
- **Docker Compose** (Nginx + PHP-FPM + MySQL) that migrates and seeds itself, so one command is
  enough. **CI** runs code style, Larastan, the test suite on MySQL with PHP 8.4 and 8.5, and a smoke
  test against the Docker stack.

## Tech stack

| Concern         | Choice                                                                |
| --------------- | --------------------------------------------------------------------- |
| Framework       | Laravel 13, PHP 8.4+ (`bcmath`)                                       |
| Auth            | Laravel Sanctum (personal access tokens)                              |
| Database        | MySQL 8.4 (Docker, CI) · SQLite (local development, fast tests)       |
| External API    | Frankfurter (ECB reference rates) through Laravel's HTTP client       |
| API docs        | Scramble: OpenAPI 3.1 generated from routes, requests and resources   |
| Quality         | PHPUnit 12, Larastan (level 6), Laravel Pint                          |
| Infrastructure  | Docker multi-stage build (PHP-FPM + Nginx), Docker Compose            |
| CI              | GitHub Actions: lint, static analysis, tests matrix, Docker smoke run |

## Getting started

### With Docker (recommended)

Requirements: Docker with Compose v2.

```bash
docker compose up -d --build --wait
```

Then open **http://localhost:8000/docs/api**. The container runs the migrations and creates a demo
user with three accounts, eight categories and two months of transactions:

| Email              | Password   |
| ------------------ | ---------- |
| `demo@example.com` | `password` |

| Variable          | Default | Purpose                                                          |
| ----------------- | ------- | ---------------------------------------------------------------- |
| `APP_PORT`        | `8000`  | Port of the API on the host                                      |
| `DB_FORWARD_PORT` | `3316`  | MySQL port on the host (bound to 127.0.0.1), for a DB client     |
| `APP_KEY`         | random  | Set it to keep the same key across restarts                      |
| `SEED_DEMO_DATA`  | `true`  | Set to `false` to start with an empty database                   |
| `FRANKFURTER_URL` | public  | Exchange-rate API base URL (point it elsewhere to test outages)  |

`docker compose down -v` stops everything and deletes the database volume.

### Without Docker

Requirements: PHP 8.4+ with `bcmath`, `pdo_sqlite` and Composer.

```bash
composer setup     # install, create .env and the SQLite database, migrate and seed
composer dev       # http://localhost:8000
```

## Using the API

All endpoints live under `/api/v1` and speak JSON. Authenticate once and send the token as a bearer
token:

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@example.com","password":"password"}' | jq -r .token)

curl -s http://localhost:8000/api/v1/reports/monthly -H "Authorization: Bearer $TOKEN"
```

| Method | Endpoint                         | Description                                                           |
| ------ | -------------------------------- | --------------------------------------------------------------------- |
| POST   | `/auth/register`, `/auth/login`  | Create a user / log in; both return a token                           |
| POST   | `/auth/logout`                   | Revoke the current token                                              |
| GET    | `/auth/me`                       | Current user                                                          |
| CRUD   | `/accounts`                      | Accounts with their computed `balance`                                |
| CRUD   | `/categories`                    | Income or expense categories (`?type=income`)                         |
| CRUD   | `/transactions`                  | Transactions in any supported currency, converted to BRL              |
| GET    | `/reports/monthly?month=2026-10` | Income, expenses, balance and per-category totals                     |
| GET    | `/currencies`                    | Currencies accepted for transactions                                  |
| GET    | `/exchange-rates?currency=USD`   | BRL value of a currency on a date (`&date=2026-10-01`, default today) |

`GET /transactions` accepts `type`, `account_id`, `category_id`, `currency`, `search`, `from`, `to`
(inclusive dates), `sort` (`occurred_on`, `amount`, prefixed with `-` for descending) and `per_page`
(1 to 100).

### Rules worth knowing

- Money is always a **decimal string** with two places (`"150.75"`); requests accept numbers or
  strings with up to two decimals.
- A transaction's **type comes from its category**, and a category's type cannot change after it is
  created ([ADR 0003](docs/adr/0003-transaction-type-comes-from-category.md)).
- Changing a transaction's amount, currency or date converts it again; editing only the description
  does not call the exchange-rate API.
- Accounts and categories that still have transactions cannot be deleted (`409 Conflict`).
- Status codes: `401` without a valid token, `404` for missing records **and** for other users'
  records, `422` for validation errors (`errors` per field), `429` after 10 login attempts per minute,
  `503` when no exchange rate can be fetched.

## Project structure

```
app/
├── Casts/Cents.php                 # integer cents in the database, decimal strings in PHP
├── Enums/                          # AccountType, TransactionType
├── Http/
│   ├── Controllers/Api/            # thin controllers, one per resource
│   ├── Middleware/                 # ForceJsonResponse for every API route
│   ├── Requests/                   # validation, including ownership of related records
│   └── Resources/                  # response format
├── Models/                         # Eloquent models, query scopes (balance, filters, sorting)
├── Policies/OwnershipPolicy.php    # one policy shared by the three resources
├── Rules/SupportedCurrency.php     # validates against the provider's currency list
├── Services/
│   ├── ExchangeRates/              # provider interface, Frankfurter client, DTO, exception
│   ├── MonthlyReport.php
│   └── TransactionService.php      # derives the type and converts to BRL
└── Support/Money.php               # exact conversions with BcMath\Number
database/                           # migrations, factories, idempotent demo seeder
docker/                             # Nginx site, PHP settings, container entrypoint
docs/adr/                           # architecture decision records
tests/                              # feature tests per resource + unit tests for Money
```

## Quality

```bash
composer check     # Pint (code style) + Larastan + the test suite
```

- **Tests** cover the main flows rather than every line: registration and token revocation,
  ownership (another user's record is indistinguishable from a missing one), balance arithmetic,
  conversion with the rate of the transaction date, the `503` path with nothing persisted, re-conversion
  only when needed, inclusive date filters with pagination, month boundaries in the report, the
  provider cache and the cents arithmetic. HTTP calls are faked, and stray requests fail the test.
- Tests run on SQLite locally and on **MySQL** in CI, so database-specific SQL is exercised on both.
- **Larastan level 6** with no baseline and no ignored errors.

## Author

**Mateus Vincenzi** — [GitHub](https://github.com/matsvincenzi) ·
[LinkedIn](https://www.linkedin.com/in/mateusvincenzi/) ·
[Instagram](https://www.instagram.com/matts.dev/)

Licensed under the [MIT License](LICENSE). Exchange rates are provided by
[Frankfurter](https://frankfurter.dev) from European Central Bank data.
