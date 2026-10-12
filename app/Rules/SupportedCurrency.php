<?php

namespace App\Rules;

use App\Services\ExchangeRates\ExchangeRateProvider;
use App\Support\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class SupportedCurrency implements ValidationRule
{
    public function __construct(private ExchangeRateProvider $exchangeRates) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === Money::BASE_CURRENCY) {
            return;
        }

        if (! is_string($value) || ! array_key_exists($value, $this->exchangeRates->currencies())) {
            $fail('The :attribute must be an uppercase ISO 4217 code listed by GET /api/v1/currencies.');
        }
    }
}
