<?php

namespace App\Currency;

use Carbon\CarbonImmutable;

/**
 * One day of ECB reference rates: units of each currency for 1 EUR.
 */
final readonly class EcbRates
{
    /**
     * @param  array<string, float>  $rates
     */
    public function __construct(public CarbonImmutable $date, public array $rates) {}
}
