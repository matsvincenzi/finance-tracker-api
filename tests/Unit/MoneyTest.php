<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    #[TestWith(['150.75', 15075])]
    #[TestWith([0.29, 29])]
    #[TestWith(['-12.5', -1250])]
    #[TestWith([7, 700])]
    public function test_converts_decimal_amounts_to_cents_without_float_drift(string|int|float $amount, int $cents): void
    {
        $this->assertSame($cents, Money::toCents($amount));
    }

    #[TestWith([15075, '150.75'])]
    #[TestWith([5, '0.05'])]
    #[TestWith([-1250, '-12.50'])]
    public function test_formats_cents_with_two_decimal_places(int $cents, string $amount): void
    {
        $this->assertSame($amount, Money::fromCents($cents));
    }

    public function test_multiplication_rounds_half_away_from_zero_to_cents(): void
    {
        $this->assertSame('100.05', Money::multiply('19.99', '5.005'));
        $this->assertSame('0.01', Money::multiply('0.01', '0.5'));
    }
}
