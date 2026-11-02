<?php

namespace App\Http\Resources;

use App\Models\AtsCompany;
use App\Sources\RunErrorKind;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One company of the ATS reader with its last result.
 *
 * @mixin AtsCompany
 */
final class CompanyResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AtsCompany $company */
        $company = $this->resource;

        return [
            'id' => $company->getKey(),
            'provider' => $company->ats->value,
            'slug' => $company->slug,
            'name' => $company->display_name,
            'enabled' => $company->enabled,
            'lastCheckedAt' => $company->last_checked_at?->utc()->toIso8601ZuluString(),
            'lastJobCount' => $company->last_job_count,
            // A row from before the error kind has no kind: "other", as ats:list shows it.
            'lastError' => $company->last_error === null ? null : [
                'message' => $company->last_error,
                'kind' => ($company->last_error_kind ?? RunErrorKind::Other)->value,
            ],
        ];
    }
}
