<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\AtsCompany;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\InvalidCompanyEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The company list of the ATS reader, with the ats:add and ats:remove rules.
 */
final class CompanyController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(CompanyResource::collection(AtsCompany::query()->orderBy('id')->get())->resolve($request));
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $name = $request->validated('name');

        try {
            $entry = CompanyEntry::parse($request->string('provider')->trim().':'.$request->string('slug')->trim(), is_string($name) ? $name : null);
        } catch (InvalidCompanyEntry $invalid) {
            return response()->json(['message' => $invalid->getMessage()], 422);
        }

        if (AtsCompany::query()->forEntry($entry)->exists()) {
            return response()->json(['message' => "{$entry->key()} is already in the list."], 422);
        }

        $company = AtsCompany::query()->create([
            'ats' => $entry->ats,
            'slug' => $entry->slug,
            'display_name' => $entry->displayName,
        ]);

        return (new CompanyResource($company->refresh()))->response()->setStatusCode(201);
    }

    public function destroy(AtsCompany $company): Response
    {
        $company->delete();

        return response()->noContent();
    }
}
