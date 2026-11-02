<?php

namespace App\Http\Controllers\Api;

use App\Currency\ExchangeRates;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/rates: the date of the stored rates and their source.
 */
final class RateController
{
    public const string SOURCE = 'European Central Bank';

    public function __invoke(ExchangeRates $rates): JsonResponse
    {
        return response()->json([
            'date' => $rates->rateDate()?->toDateString(),
            'source' => self::SOURCE,
        ]);
    }
}
