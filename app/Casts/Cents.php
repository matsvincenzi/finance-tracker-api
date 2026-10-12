<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * The column holds integer cents while the attribute reads and writes decimal strings ("150.75"),
 * so SQL aggregates stay exact and no float ever reaches a balance. Only for NOT NULL columns.
 * See ADR 0001.
 *
 * @implements CastsAttributes<string, string|int|float>
 */
final class Cents implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return Money::fromCents((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return Money::toCents($value);
    }
}
