<?php

namespace App\Http\Requests;

use App\Rules\SupportedCurrency;
use App\Services\ExchangeRates\ExchangeRateProvider;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExchangeRateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(ExchangeRateProvider $exchangeRates): array
    {
        return [
            'currency' => ['required', 'string', 'size:3', Rule::notIn([Money::BASE_CURRENCY]), new SupportedCurrency($exchangeRates)],
            // The ECB reference rates start on 1999-01-04.
            'date' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:1999-01-04', 'before_or_equal:today'],
        ];
    }
}
