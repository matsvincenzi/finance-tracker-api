<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Services\ExchangeRates\ExchangeRateProvider;
use App\Services\ExchangeRates\ExchangeRateUnavailable;
use App\Support\Money;

final class TransactionService
{
    public function __construct(private readonly ExchangeRateProvider $exchangeRates) {}

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ExchangeRateUnavailable
     */
    public function create(User $user, array $attributes): Transaction
    {
        return $this->save($user->transactions()->make($attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ExchangeRateUnavailable
     */
    public function update(Transaction $transaction, array $attributes): Transaction
    {
        return $this->save($transaction->fill($attributes));
    }

    private function save(Transaction $transaction): Transaction
    {
        if ($transaction->isDirty('category_id')) {
            $transaction->type = $transaction->category()->firstOrFail()->type;
        }

        if ($transaction->isDirty(['amount', 'currency', 'occurred_on'])) {
            $this->convertToBaseCurrency($transaction);
        }

        $transaction->save();

        return $transaction->load(['account', 'category']);
    }

    private function convertToBaseCurrency(Transaction $transaction): void
    {
        $rate = $transaction->currency === Money::BASE_CURRENCY
            ? '1'
            : $this->exchangeRates->rate($transaction->currency, Money::BASE_CURRENCY, $transaction->occurred_on)->rate;

        $transaction->exchange_rate = $rate;
        $transaction->amount_brl = Money::multiply($transaction->amount, $rate);
    }
}
