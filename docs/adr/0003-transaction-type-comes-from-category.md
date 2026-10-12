# 0003 — A transaction's type comes from its category

- **Status:** accepted
- **Date:** 2026-10-12

## Context

Both transactions and categories are either income or expense. If clients send both values, the API
must reject combinations such as an expense filed under "Salary", and every partial update has to
re-check the pair.

## Decision

- Categories have a `type` that is set on creation and **cannot be changed** afterwards (the update
  request marks it as `prohibited`).
- Transactions do not accept a `type`. `TransactionService` copies it from the category whenever the
  category changes, and stores it on the transaction so filters, balances and reports do not need a
  join.

## Consequences

- An inconsistent transaction cannot be created through the API, and there is one fewer validation
  rule to maintain.
- Reclassifying a category from expense to income means creating a new category and moving its
  transactions, which is a deliberate, visible operation instead of a silent rewrite of history.
