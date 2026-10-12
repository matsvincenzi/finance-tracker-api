# 0001 — Money is stored as integer cents and exposed as decimal strings

- **Status:** accepted
- **Date:** 2026-10-12

## Context

Every figure the API returns (balances, report totals, converted amounts) is a sum or a product of
user input. Binary floats cannot represent most decimal fractions (`0.1 + 0.2 !== 0.3`), and
`DECIMAL` columns only help if every layer keeps the value exact: SQLite, used for local development
and tests, stores `DECIMAL` as a float, and PHP turns JSON numbers into floats.

## Decision

- Monetary columns (`initial_balance`, `amount`, `amount_brl`) are `BIGINT` holding cents. Sums,
  balances and report totals are computed in SQL over integers, which is exact on every database.
- The `App\Casts\Cents` cast turns cents into decimal strings with two places (`"150.75"`) when
  reading, and decimal strings or numbers into cents when writing. Application code and API
  responses never see cents or floats.
- Arithmetic that can produce fractions of a cent (currency conversion) uses PHP 8.4's
  `BcMath\Number` and rounds half away from zero to two places (`App\Support\Money`).
- Exchange rates are stored as `DECIMAL(14, 6)` and returned as strings. They are never summed, so
  their precision only has to match the source.
- The API accepts amounts as JSON numbers or strings with at most two decimal places and always
  returns strings.

## Consequences

- Clients must parse money as decimal strings, not as JSON numbers. This is stated in the API
  documentation.
- Columns are `NOT NULL`; the cast does not handle `null` and must not be used on nullable columns.
- Adding a currency with a different number of minor units (JPY has none, BHD has three) as an
  account currency would need a per-currency scale. Today every balance is in BRL (see ADR 0002), so
  a fixed scale of two is correct.
