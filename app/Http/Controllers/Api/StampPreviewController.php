<?php

namespace App\Http\Controllers\Api;

use App\List\StampPreview;
use App\Parsers\Location\Countries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/stamp-preview?country=DE: how two stored jobs look for a country, before it is saved.
 */
final class StampPreviewController
{
    public function __invoke(Request $request, StampPreview $preview): JsonResponse
    {
        $country = $request->query('country');

        if (! is_string($country) || ! in_array($country, Countries::codes(), true)) {
            return response()->json(['message' => 'Choose a country from the list.'], 422);
        }

        return response()->json(['jobs' => $preview->for($country)]);
    }
}
