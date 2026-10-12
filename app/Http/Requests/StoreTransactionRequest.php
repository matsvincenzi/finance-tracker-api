<?php

namespace App\Http\Requests;

use App\Models\Account;
use App\Models\Category;
use App\Rules\SupportedCurrency;
use App\Services\ExchangeRates\ExchangeRateProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(ExchangeRateProvider $exchangeRates): array
    {
        $userId = $this->user()?->id;

        return [
            'account_id' => ['required', 'integer', Rule::exists(Account::class, 'id')->where('user_id', $userId)],
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')->where('user_id', $userId)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999.99'],
            'currency' => ['sometimes', 'required', 'string', 'size:3', new SupportedCurrency($exchangeRates)],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }
}
