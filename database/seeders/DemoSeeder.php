<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for trying the API right away. Runs on every container start, so it must be
 * idempotent, and it only records BRL transactions so seeding never depends on the network.
 */
class DemoSeeder extends Seeder
{
    public const string EMAIL = 'demo@example.com';

    public function run(TransactionService $transactions): void
    {
        if (User::query()->where('email', self::EMAIL)->exists()) {
            return;
        }

        DB::transaction(function () use ($transactions) {
            $user = User::create(['name' => 'Demo User', 'email' => self::EMAIL, 'password' => 'password']);

            $checking = $user->accounts()->create(['name' => 'Checking account', 'type' => AccountType::Checking, 'initial_balance' => '2500.00']);
            $card = $user->accounts()->create(['name' => 'Credit card', 'type' => AccountType::CreditCard, 'initial_balance' => '0.00']);
            $user->accounts()->create(['name' => 'Emergency fund', 'type' => AccountType::Savings, 'initial_balance' => '10000.00']);

            $category = $this->createCategories($user);

            // [months ago, day of month, account, category, description, amount]; dates later than
            // today are moved to today so the current month always has a complete picture.
            $entries = [
                [1, 5, $checking, 'Salary', 'Monthly salary', '5200.00'],
                [1, 8, $card, 'Groceries', 'Supermarket', '298.02'],
                [1, 10, $checking, 'Housing', 'Rent', '1650.00'],
                [1, 12, $card, 'Subscriptions', 'Streaming service', '44.90'],
                [1, 15, $checking, 'Transport', 'Fuel', '205.00'],
                [1, 22, $card, 'Groceries', 'Supermarket', '274.18'],
                [1, 26, $card, 'Leisure', 'Dinner with friends', '138.50'],
                [0, 5, $checking, 'Salary', 'Monthly salary', '5200.00'],
                [0, 8, $card, 'Groceries', 'Supermarket', '312.47'],
                [0, 10, $checking, 'Housing', 'Rent', '1650.00'],
                [0, 12, $card, 'Subscriptions', 'Streaming service', '44.90'],
                [0, 15, $checking, 'Transport', 'Fuel', '220.00'],
                [0, 18, $checking, 'Health', 'Pharmacy', '87.35'],
                [0, 20, $checking, 'Freelance', 'Landing page project', '1800.00'],
                [0, 25, $card, 'Leisure', 'Cinema tickets', '64.00'],
            ];

            foreach ($entries as [$monthsAgo, $day, $account, $categoryName, $description, $amount]) {
                $transactions->create($user, [
                    'account_id' => $account->id,
                    'category_id' => $category[$categoryName]->id,
                    'description' => $description,
                    'amount' => $amount,
                    'occurred_on' => today()->subMonthsNoOverflow($monthsAgo)->setDay($day)->min(today())->toDateString(),
                ]);
            }
        });
    }

    /**
     * @return array<string, Category>
     */
    private function createCategories(User $user): array
    {
        $definitions = [
            'Salary' => [TransactionType::Income, '#16A34A'],
            'Freelance' => [TransactionType::Income, '#0EA5E9'],
            'Housing' => [TransactionType::Expense, '#9333EA'],
            'Groceries' => [TransactionType::Expense, '#F59E0B'],
            'Transport' => [TransactionType::Expense, '#64748B'],
            'Health' => [TransactionType::Expense, '#EF4444'],
            'Leisure' => [TransactionType::Expense, '#EC4899'],
            'Subscriptions' => [TransactionType::Expense, '#6366F1'],
        ];

        $categories = [];

        foreach ($definitions as $name => [$type, $color]) {
            $categories[$name] = $user->categories()->create(['name' => $name, 'type' => $type, 'color' => $color]);
        }

        return $categories;
    }
}
