<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTransactionsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(TransactionType::class)],
            'account_id' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'integer'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'search' => ['sometimes', 'string', 'max:100'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'sort' => ['sometimes', Rule::in(['occurred_on', '-occurred_on', 'amount', '-amount'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
