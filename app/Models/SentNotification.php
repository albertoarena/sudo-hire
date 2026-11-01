<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One summary notification that was sent, e.g. "6 new jobs: 4 Laravel, 2 PHP".
 *
 * @property int $id
 * @property CarbonImmutable $sent_at
 * @property int $job_count
 * @property string $text
 */
#[Fillable(['sent_at', 'job_count', 'text'])]
class SentNotification extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'immutable_datetime',
            'job_count' => 'integer',
        ];
    }
}
