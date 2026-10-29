<?php

namespace App\Sources\Ats;

use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\Bind;

/**
 * Where the ATS reader gets its companies and stores the last result of each.
 * The first list is the ats_companies table. A remote list (Premium) can
 * replace it later.
 */
#[Bind(DatabaseCompanyList::class)]
interface CompanyList
{
    /**
     * The enabled companies.
     *
     * @return list<CompanyEntry>
     */
    public function companies(): array;

    public function lastCheckedAt(CompanyEntry $company): ?CarbonImmutable;

    public function recordJobs(CompanyEntry $company, int $jobCount, CarbonImmutable $checkedAt): void;

    public function recordError(CompanyEntry $company, string $error, CarbonImmutable $checkedAt): void;
}
