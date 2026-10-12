<?php

namespace App\Services\ExchangeRates;

use Carbon\CarbonImmutable;

final readonly class ExchangeRate
{
    /**
     * @param  string  $rate  How many units of `$to` one unit of `$from` buys, as a decimal string.
     * @param  CarbonImmutable  $date  Date the quote refers to: the last business day on or before the requested one.
     */
    public function __construct(
        public string $from,
        public string $to,
        public string $rate,
        public CarbonImmutable $date,
    ) {}
}
