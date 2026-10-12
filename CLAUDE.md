# finance-tracker-api — Repository Rules

> Personal finance REST API (Laravel 13). Accounts, categories and transactions in any currency
> supported by Frankfurter, converted to BRL on the transaction date. API only: no views, no frontend
> build.

## Language

Everything committed here — code, comments, tests, commit messages, branch names — is in
**English**.

## Stack

- Laravel 13 on PHP 8.4+ (`bcmath` required), Sanctum tokens, Scramble for OpenAPI docs
- MySQL 8.4 in Docker and CI, SQLite locally and for fast tests
- PHPUnit 12, Larastan level 6, Pint (Laravel preset)

## Conventions

- Routes live in `routes/api.php` under the `api/v1` prefix. Controllers stay thin: validation in a
  Form Request, output through an API Resource, business rules in `app/Services`.
- **Money:** columns are `BIGINT` cents with the `Cents` cast; code and JSON only see decimal strings.
  Multiply or divide through `App\Support\Money` (`BcMath\Number`), never with floats (ADR 0001).
- **Ownership:** models owned by a user use `#[UsePolicy(OwnershipPolicy::class)]`; controller
  actions on a single record declare `#[Authorize('view|update|delete', 'param')]` so the policy runs
  before validation. Lists are always scoped through `$user->relation()`.
- **Dates:** `occurred_on` is a `date` cast, which SQLite stores with a time part. Upper bounds use
  the `occurredBefore()` scope (exclusive next day), never `<=` on a date string.
- **External API:** go through `ExchangeRateProvider`; never call `Http` from controllers. Tests must
  fake every request (`Http::preventStrayRequests()` is on in the base `TestCase`).
- Models are strict outside production (`Model::shouldBeStrict()`): eager-load relations, use
  `whenLoaded()` / `whenHas()` in resources.
- New migrations need a `down()`; foreign keys from transactions restrict deletes.

## Comments

Default is **no comment**. Keep one only when deleting it would leave a reader surprised or misled
(an external constraint, a rule that contradicts what the code looks like it does, a security
caveat). PHPDoc is for types and API documentation (Scramble reads controller and resource
docblocks), not for repeating signatures.

## Checks

```bash
composer check    # pint --test + phpstan + php artisan test
composer format   # apply Pint
```

`docker compose up -d --build --wait` runs the production-like stack on http://localhost:8000.

## Git workflow

- Branches: `main` / `type/short-description` (`feat|fix|refactor|docs|chore|test|perf|build|ci`).
- Conventional Commits, English, imperative subject. No usernames or tracker IDs in branch names.
