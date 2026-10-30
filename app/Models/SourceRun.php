<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One run of one source, with its counts or its error.
 *
 * @property string $source
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $finished_at
 * @property string|null $error
 * @property string|null $error_kind certificate, http, parse or other
 * @property int $items_fetched
 * @property int $items_skipped
 * @property float|null $unknown_eligibility_share
 * @property float|null $unknown_salary_share
 */
#[Fillable(['source', 'started_at', 'finished_at', 'error', 'error_kind', 'items_fetched', 'items_skipped', 'unknown_eligibility_share', 'unknown_salary_share'])]
class SourceRun extends Model
{
    /**
     * Finished runs with no error.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSucceeded(Builder $query): Builder
    {
        return $query->whereNotNull('finished_at')->whereNull('error_kind');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'items_fetched' => 'integer',
            'items_skipped' => 'integer',
            'unknown_eligibility_share' => 'float',
            'unknown_salary_share' => 'float',
        ];
    }
}
