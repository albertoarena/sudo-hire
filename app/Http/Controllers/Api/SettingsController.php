<?php

namespace App\Http\Controllers\Api;

use App\List\UnseenBadge;
use App\Settings\InvalidSettings;
use App\Settings\Settings;
use App\Settings\SettingsStore;
use App\Sources\UnknownSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET and PUT /api/settings, in the shape of Settings::toArray().
 * A PUT keeps the stored value of each setting that it does not send.
 */
final class SettingsController
{
    public function show(SettingsStore $store): JsonResponse
    {
        return response()->json($store->load()->toArray());
    }

    public function update(Request $request, SettingsStore $store, UnseenBadge $badge): JsonResponse
    {
        try {
            $settings = Settings::fromArray(array_merge($store->load()->toArray(), $request->all()));
            $store->save($settings);
        } catch (InvalidSettings|UnknownSource $invalid) {
            return response()->json(['message' => $invalid->getMessage()], 422);
        }

        $badge->refresh();

        return response()->json($settings->toArray());
    }
}
