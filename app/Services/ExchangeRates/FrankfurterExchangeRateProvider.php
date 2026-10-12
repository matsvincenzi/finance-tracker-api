<?php

namespace App\Services\ExchangeRates;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Daily reference rates published by the European Central Bank, served by https://frankfurter.dev.
 */
final class FrankfurterExchangeRateProvider implements ExchangeRateProvider
{
    public function __construct(
        #[Config('services.frankfurter.url')] private readonly string $baseUrl,
        #[Config('services.frankfurter.timeout')] private readonly int $timeout,
    ) {}

    public function rate(string $from, string $to, CarbonInterface $date): ExchangeRate
    {
        $day = $date->toDateString();

        // Past quotes never change; today's may still be replaced once the ECB publishes.
        $ttl = $date->isToday() ? now()->addHour() : now()->addMonth();

        /** @var array{rate: string, date: string} $quote */
        $quote = Cache::remember("exchange-rates:{$from}:{$to}:{$day}", $ttl, function () use ($from, $to, $day): array {
            $payload = $this->get($day, ['base' => $from, 'symbols' => $to]);
            $rate = data_get($payload, "rates.{$to}");

            if (! is_numeric($rate) || ! is_string($payload['date'] ?? null)) {
                throw new ExchangeRateUnavailable;
            }

            return ['rate' => number_format((float) $rate, 6, '.', ''), 'date' => $payload['date']];
        });

        return new ExchangeRate($from, $to, $quote['rate'], CarbonImmutable::parse($quote['date']));
    }

    public function currencies(): array
    {
        /** @var array<string, string> */
        return Cache::remember('exchange-rates:currencies', now()->addDay(), fn (): array => $this->get('currencies'));
    }

    /**
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     *
     * @throws ExchangeRateUnavailable
     */
    private function get(string $path, array $query = []): array
    {
        try {
            $payload = Http::baseUrl($this->baseUrl)
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(2, 200, fn (Throwable $exception): bool => $this->isTransient($exception))
                ->get($path, $query)
                ->json();
        } catch (ConnectionException|RequestException $exception) {
            // HTTP exceptions are not logged by Laravel, so record the upstream cause here.
            report($exception);

            throw new ExchangeRateUnavailable($exception);
        }

        if (! is_array($payload)) {
            throw new ExchangeRateUnavailable;
        }

        return $payload;
    }

    private function isTransient(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException && $exception->response->serverError());
    }
}
