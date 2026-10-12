<?php

namespace Tests\Feature;

use App\Services\ExchangeRates\ExchangeRateProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FrankfurterExchangeRateProviderTest extends TestCase
{
    public function test_reports_the_quote_date_and_caches_it(): void
    {
        Http::fake([
            '*' => Http::response(['amount' => 1.0, 'base' => 'USD', 'date' => '2026-03-06', 'rates' => ['BRL' => 5.7123]]),
        ]);
        $provider = $this->app->make(ExchangeRateProvider::class);

        $first = $provider->rate('USD', 'BRL', CarbonImmutable::parse('2026-03-08'));
        $second = $provider->rate('USD', 'BRL', CarbonImmutable::parse('2026-03-08'));

        $this->assertSame('5.712300', $first->rate);
        $this->assertSame('2026-03-06', $first->date->toDateString());
        $this->assertEquals($first, $second);
        Http::assertSentCount(1);
    }
}
