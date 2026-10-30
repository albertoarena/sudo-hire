<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Another source of a stored job, shown as "also on". It keeps its own links.
 *
 * @property int $job_id
 * @property string $source
 * @property string $source_job_id
 * @property string $source_url
 * @property string $apply_url
 * @property string $attribution
 * @property CarbonImmutable $fetched_at
 */
#[Fillable(['job_id', 'source', 'source_job_id', 'source_url', 'apply_url', 'attribution', 'fetched_at'])]
class AlsoOnSource extends Model
{
    protected $table = 'job_sources';

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fetched_at' => 'immutable_datetime',
        ];
    }
}
