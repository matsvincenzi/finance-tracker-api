<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExchangeRateRequest;
use App\Services\ExchangeRates\ExchangeRateProvider;
use App\Support\Money;
use Illuminate\Http\JsonResponse;

final class ExchangeRateController extends Controller
{
    public function __construct(private readonly ExchangeRateProvider $exchangeRates) {}

    /**
     * List the currencies transactions can be recorded in.
     */
    public function currencies(): JsonResponse
    {
        $currencies = collect($this->exchangeRates->currencies())
            ->map(fn (string $name, string $code): array => ['code' => $code, 'name' => $name])
            ->values();

        return response()->json(['data' => $currencies]);
    }

    /**
     * Quote how many BRL one unit of a currency was worth on a date (default: today).
     */
    public function show(ExchangeRateRequest $request): JsonResponse
    {
        $quote = $this->exchangeRates->rate(
            $request->string('currency')->value(),
            Money::BASE_CURRENCY,
            $request->date('date') ?? today(),
        );

        return response()->json(['data' => [
            'from' => $quote->from,
            'to' => $quote->to,
            'rate' => $quote->rate,
            /** Last business day on or before the requested date. */
            'date' => $quote->date->toDateString(),
        ]]);
    }
}
