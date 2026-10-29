<?php

namespace App\Parsers\Location;

use App\Schema\Eligibility;
use App\Schema\WorkMode;

/**
 * What the location parser reads from one location text.
 */
final readonly class ParsedLocation
{
    /**
     * @param  float|null  $timezoneMin  UTC offset in hours, e.g. -9.5
     * @param  float|null  $timezoneMax  UTC offset in hours, e.g. 5.75
     * @param  string|null  $officeLocation  free text, for hybrid and on-site jobs only
     */
    public function __construct(
        public Eligibility $eligibility,
        public WorkMode $workMode,
        public ?float $timezoneMin,
        public ?float $timezoneMax,
        public ?string $officeLocation,
    ) {}
}
