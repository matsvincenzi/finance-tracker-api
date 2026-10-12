<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonInterface;

final class MonthlyReport
{
    /**
     * @return array{
     *     month: string,
     *     currency: string,
     *     income: string,
     *     expense: string,
     *     balance: string,
     *     categories: list<array{id: int, name: string, type: string, color: string|null, total: string, transactions_count: int, share: float}>,
     * }
     */
    public function for(User $user, CarbonInterface $month): array
    {
        $start = $month->toImmutable()->startOfMonth();

        $rows = $user->transactions()
            ->where('occurred_on', '>=', $start->toDateString())
            ->occurredBefore($start->addMonth())
            ->toBase()
            ->select('category_id', 'type')
            ->selectRaw('SUM(amount_brl) AS total, COUNT(*) AS transactions_count')
            ->groupBy('category_id', 'type')
            ->orderByDesc('total')
            ->get();

        $categories = Category::query()->findMany($rows->pluck('category_id'))->keyBy('id');

        $totals = [
            TransactionType::Income->value => (int) $rows->where('type', TransactionType::Income->value)->sum('total'),
            TransactionType::Expense->value => (int) $rows->where('type', TransactionType::Expense->value)->sum('total'),
        ];

        return [
            'month' => $start->format('Y-m'),
            'currency' => Money::BASE_CURRENCY,
            'income' => Money::fromCents($totals[TransactionType::Income->value]),
            'expense' => Money::fromCents($totals[TransactionType::Expense->value]),
            'balance' => Money::fromCents($totals[TransactionType::Income->value] - $totals[TransactionType::Expense->value]),
            'categories' => $rows->map(fn (object $row): array => [
                'id' => (int) $row->category_id,
                'name' => $categories[$row->category_id]->name,
                'type' => $row->type,
                'color' => $categories[$row->category_id]->color,
                'total' => Money::fromCents((int) $row->total),
                'transactions_count' => (int) $row->transactions_count,
                /** Percentage of the month's income or expenses, depending on the type. */
                'share' => round((int) $row->total / $totals[$row->type] * 100, 1),
            ])->values()->all(),
        ];
    }
}
