<?php

namespace App\Sources\Ats;

use Carbon\CarbonImmutable;

/**
 * One raw job of the ATS reader. It carries its company entry, because
 * JobSource::normalise() gets only the raw item.
 */
final readonly class AtsItem
{
    /**
     * @param  array<mixed>  $data  the job as the ATS returns it
     */
    public function __construct(
        public CompanyEntry $company,
        public array $data,
        public CarbonImmutable $fetchedAt,
    ) {}
}
