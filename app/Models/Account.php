<?php

namespace App\Models;

use App\Casts\Cents;
use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Policies\OwnershipPolicy;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'initial_balance'])]
#[UsePolicy(OwnershipPolicy::class)]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'initial_balance' => Cents::class,
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
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Adds a `balance` attribute: the initial balance plus income minus expenses, in BRL.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withBalance(Builder $query): void
    {
        $balance = Transaction::query()
            ->selectRaw(
                'accounts.initial_balance + COALESCE(SUM(CASE WHEN transactions.type = ? THEN transactions.amount_brl ELSE -transactions.amount_brl END), 0)',
                [TransactionType::Income->value],
            )
            ->whereColumn('transactions.account_id', 'accounts.id');

        $query->addSelect(['balance' => $balance])->withCasts(['balance' => Cents::class]);
    }
}
