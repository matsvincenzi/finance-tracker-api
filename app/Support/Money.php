<?php

namespace App\Support;

use BcMath\Number;

final class Money
{
    public const string BASE_CURRENCY = 'BRL';

    public static function toCents(string|int|float $amount): int
    {
        return (int) (string) (new Number((string) $amount))->mul(100)->round();
    }

    public static function fromCents(int $cents): string
    {
        return (string) (new Number($cents))->div(100, 2);
    }

    public static function multiply(string $amount, string $factor): string
    {
        return (string) (new Number($amount))->mul($factor)->round(2);
    }
}
