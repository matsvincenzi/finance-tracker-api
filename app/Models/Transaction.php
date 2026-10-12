<?php

namespace App\Models;

use App\Casts\Cents;
use App\Enums\TransactionType;
use App\Policies\OwnershipPolicy;
use App\Support\Money;
use Carbon\CarbonInterface;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['account_id', 'category_id', 'description', 'amount', 'currency', 'occurred_on'])]
#[UsePolicy(OwnershipPolicy::class)]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => Money::BASE_CURRENCY,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => Cents::class,
            'exchange_rate' => 'decimal:6',
            'amount_brl' => Cents::class,
            'occurred_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['account_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('account_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('category_id', $id))
            ->when($filters['currency'] ?? null, fn (Builder $query, string $currency) => $query->where('currency', $currency))
            ->when($filters['search'] ?? null, fn (Builder $query, string $term) => $query->where('description', 'like', "%{$term}%"))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('occurred_on', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->occurredBefore(Carbon::parse($to)->addDay()));
    }

    /**
     * Upper bounds are exclusive because SQLite stores date casts with a time part
     * ("2026-10-31 00:00:00" > "2026-10-31"), so an inclusive comparison would drop the last day.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function occurredBefore(Builder $query, CarbonInterface $date): void
    {
        $query->where('occurred_on', '<', $date->toDateString());
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sorted(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-') === 'amount' ? 'amount_brl' : 'occurred_on';

        $query->orderBy($column, $direction)->orderBy('id', $direction);
    }
}
