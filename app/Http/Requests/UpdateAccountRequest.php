<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique(Account::class)->where('user_id', $this->user()?->id)->ignore($this->route('account')),
            ],
            'type' => ['sometimes', 'required', Rule::enum(AccountType::class)],
            'initial_balance' => ['sometimes', 'numeric', 'decimal:0,2', 'between:-999999999.99,999999999.99'],
        ];
    }
}
