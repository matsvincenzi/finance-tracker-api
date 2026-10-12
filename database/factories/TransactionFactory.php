<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Account and category default to ones owned by the same user, with a matching type.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = (string) fake()->randomFloat(2, 1, 500);

        return [
            'user_id' => User::factory(),
            'type' => TransactionType::Expense,
            'account_id' => fn (array $attributes) => Account::factory()->state(['user_id' => $attributes['user_id']]),
            'category_id' => fn (array $attributes) => Category::factory()->state([
                'user_id' => $attributes['user_id'],
                'type' => $attributes['type'],
            ]),
            'description' => fake()->sentence(3),
            'amount' => $amount,
            'currency' => Money::BASE_CURRENCY,
            'exchange_rate' => '1',
            'amount_brl' => $amount,
            'occurred_on' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
        ];
    }
}
