<?php

namespace App\Sources\Ats;

use App\Models\AtsCompany;
use App\Sources\RunErrorKind;
use Carbon\CarbonImmutable;

/**
 * The company list in the ats_companies table.
 */
final class DatabaseCompanyList implements CompanyList
{
    public function companies(): array
    {
        return array_values(AtsCompany::query()
            ->where('enabled', true)
            ->orderBy('id')
            ->get()
            ->map(fn (AtsCompany $company) => $company->toEntry())
            ->all());
    }

    public function lastCheckedAt(CompanyEntry $company): ?CarbonImmutable
    {
        return AtsCompany::query()->forEntry($company)->first()?->last_checked_at;
    }

    public function recordJobs(CompanyEntry $company, int $jobCount, CarbonImmutable $checkedAt): void
    {
        AtsCompany::query()->forEntry($company)->update([
            'last_checked_at' => $checkedAt,
            'last_job_count' => $jobCount,
            'last_error' => null,
            'last_error_kind' => null,
        ]);
    }

    public function recordError(CompanyEntry $company, string $error, RunErrorKind $kind, CarbonImmutable $checkedAt): void
    {
        AtsCompany::query()->forEntry($company)->update([
            'last_checked_at' => $checkedAt,
            'last_job_count' => null,
            'last_error' => $error,
            'last_error_kind' => $kind,
        ]);
    }
}
