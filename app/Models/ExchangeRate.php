<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One ECB reference rate: units of the currency for 1 EUR.
 *
 * @property string $currency
 * @property float $rate
 * @property CarbonImmutable $rate_date
 * @property CarbonImmutable $fetched_at
 */
#[Fillable(['currency', 'rate', 'rate_date', 'fetched_at'])]
class ExchangeRate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'float',
            'rate_date' => 'immutable_date',
            'fetched_at' => 'immutable_datetime',
        ];
    }
}
