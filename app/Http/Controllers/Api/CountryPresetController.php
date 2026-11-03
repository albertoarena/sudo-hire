<?php

namespace App\Http\Controllers\Api;

use App\Settings\SystemCountry;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/country-preset: the country of the Mac region, for the first run. Not stored.
 */
final class CountryPresetController
{
    public function __invoke(SystemCountry $country): JsonResponse
    {
        return response()->json(['country' => $country->code()]);
    }
}
