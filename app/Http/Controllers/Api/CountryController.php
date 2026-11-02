<?php

namespace App\Http\Controllers\Api;

use App\Parsers\Location\Countries;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/countries: the country table for the country field, sorted by name.
 */
final class CountryController
{
    public function __invoke(): JsonResponse
    {
        $countries = array_map(fn (string $code) => ['code' => $code, 'name' => Countries::names($code)[0] ?? $code], Countries::codes());
        usort($countries, fn (array $a, array $b) => strnatcasecmp($a['name'], $b['name']));

        return response()->json($countries);
    }
}
