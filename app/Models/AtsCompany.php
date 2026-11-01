<?php

namespace App\Models;

use App\Sources\Ats\Ats;
use App\Sources\Ats\CompanyEntry;
use App\Sources\RunErrorKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One company of the ATS reader's company list.
 *
 * @property Ats $ats
 * @property string $slug
 * @property string $display_name
 * @property bool $enabled
 * @property CarbonImmutable|null $last_checked_at
 * @property int|null $last_job_count
 * @property string|null $last_error
 * @property RunErrorKind|null $last_error_kind
 */
#[Fillable(['ats', 'slug', 'display_name', 'enabled', 'last_checked_at', 'last_job_count', 'last_error', 'last_error_kind'])]
class AtsCompany extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'enabled' => true,
    ];

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForEntry(Builder $query, CompanyEntry $entry): Builder
    {
        return $query->where('ats', $entry->ats)->where('slug', $entry->slug);
    }

    public function toEntry(): CompanyEntry
    {
        return new CompanyEntry($this->ats, $this->slug, $this->display_name);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ats' => Ats::class,
            'enabled' => 'boolean',
            'last_checked_at' => 'immutable_datetime',
            'last_job_count' => 'integer',
            'last_error_kind' => RunErrorKind::class,
        ];
    }
}
