# 0002 — Exchange rates come from Frankfurter, behind an interface, cached by date

- **Status:** accepted
- **Date:** 2026-10-12

## Context

Transactions can be recorded in a foreign currency (a subscription billed in USD, a trip paid in
EUR), but balances and reports only make sense in one currency. The converted value has to use the
rate of the day the money moved, not the rate of the day someone looks at the report.

Candidates were [AwesomeAPI](https://docs.awesomeapi.com.br/api-de-moedas) (Brazilian, quotes against
BRL) and [Frankfurter](https://frankfurter.dev) (European Central Bank reference rates). Both are free
and need no API key.

## Decision

- **Base currency is BRL.** Every account balance and report is in BRL. Each transaction keeps its
  original `amount` and `currency`, plus the `exchange_rate` used and the resulting `amount_brl`, so
  the conversion is auditable and never recomputed silently.
- **Frankfurter** is the provider: historical rates by date are a first-class endpoint
  (`/v1/2026-03-07?base=USD&symbols=BRL`), weekends and holidays resolve to the previous business
  day, and `/v1/currencies` lists what can be converted, which the API reuses for validation.
- The application depends on the `ExchangeRateProvider` interface; `FrankfurterExchangeRateProvider`
  is bound to it with the `#[Bind]` attribute. Swapping providers, or faking one in tests, touches no
  controller or service.
- **Caching:** quotes for past dates never change, so they are cached for a month; today's quote is
  cached for an hour because the ECB publishes it in the afternoon. The currency list is cached for a
  day. Cache keys include both currencies and the date.
- **Failure handling:** 5 s timeout and two retries for connection errors and 5xx responses only.
  When no rate can be obtained, the API answers `503 Service Unavailable` with `Retry-After: 60` and
  stores nothing; the upstream error is logged. BRL transactions never call the provider, so the API
  keeps working for them during an outage.
- A transaction is converted again only when its amount, currency or date changes.

## Consequences

- Only the ~30 currencies published by the ECB are accepted (ARS, for example, is not).
- Rates are daily reference rates, not the rate a bank or card actually charged. A future field could
  let the user override the rate for a given transaction.
- Recording a foreign-currency transaction depends on a third-party service being reachable; the
  cache reduces, but does not remove, that dependency.
