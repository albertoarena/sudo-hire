<?php

namespace Tests\Fakes;

use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\CompanyList;
use App\Sources\RunErrorKind;
use Carbon\CarbonImmutable;

/**
 * A company list in memory, for tests of the ATS reader.
 */
final class InMemoryCompanyList implements CompanyList
{
    /** @var array<string, CarbonImmutable> */
    public array $checkedAt = [];

    /** @var array<string, int> */
    public array $jobCounts = [];

    /** @var array<string, string> */
    public array $errors = [];

    /** @var array<string, RunErrorKind> */
    public array $errorKinds = [];

    /**
     * @param  list<CompanyEntry>  $companies
     */
    public function __construct(private array $companies) {}

    public function companies(): array
    {
        return $this->companies;
    }

    public function lastCheckedAt(CompanyEntry $company): ?CarbonImmutable
    {
        return $this->checkedAt[$company->key()] ?? null;
    }

    public function recordJobs(CompanyEntry $company, int $jobCount, CarbonImmutable $checkedAt): void
    {
        $this->checkedAt[$company->key()] = $checkedAt;
        $this->jobCounts[$company->key()] = $jobCount;
        unset($this->errors[$company->key()], $this->errorKinds[$company->key()]);
    }

    public function recordError(CompanyEntry $company, string $error, RunErrorKind $kind, CarbonImmutable $checkedAt): void
    {
        $this->checkedAt[$company->key()] = $checkedAt;
        $this->errors[$company->key()] = $error;
        $this->errorKinds[$company->key()] = $kind;
        unset($this->jobCounts[$company->key()]);
    }
}
