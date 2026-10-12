<?php

namespace App\Services\ExchangeRates;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

final class ExchangeRateUnavailable extends ServiceUnavailableHttpException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            retryAfter: 60,
            message: 'Exchange rates are temporarily unavailable. Please try again in a few minutes.',
            previous: $previous,
        );
    }
}
