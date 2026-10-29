<?php

namespace App\Sources\Ats;

use App\Schema\NormalisedJob;
use App\Sources\SkipItem;

/**
 * Reads the jobs of one ATS. The ATS source has one reader for each ATS.
 */
interface AtsReader
{
    public function ats(): Ats;

    /**
     * The raw jobs of one company.
     *
     * @return list<array<mixed>>
     *
     * @throws ReadFailed
     */
    public function read(CompanyEntry $company): array;

    /**
     * @throws SkipItem
     */
    public function normalise(AtsItem $item): NormalisedJob;
}
