<?php

namespace App\Services\ExchangeRates;

use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\Bind;

#[Bind(FrankfurterExchangeRateProvider::class)]
interface ExchangeRateProvider
{
    /**
     * @throws ExchangeRateUnavailable
     */
    public function rate(string $from, string $to, CarbonInterface $date): ExchangeRate;

    /**
     * @return array<string, string> ISO 4217 code => currency name
     *
     * @throws ExchangeRateUnavailable
     */
    public function currencies(): array;
}
