<?php

namespace App\Models;

use App\List\JobState;
use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One stored job post. Eligibility and salary are flat columns (see docs/DECISIONS.md).
 *
 * @property int $id
 * @property string $source
 * @property string $source_job_id
 * @property string $source_url
 * @property string $apply_url
 * @property string $title
 * @property string $company
 * @property string|null $description
 * @property list<string> $tags
 * @property WorkMode $work_mode
 * @property string $eligibility_type
 * @property list<string> $eligibility_regions
 * @property list<string> $eligibility_countries
 * @property string|null $office_location
 * @property float|null $timezone_min
 * @property float|null $timezone_max
 * @property float|null $salary_min
 * @property float|null $salary_max
 * @property string|null $salary_currency
 * @property SalaryPeriod|null $salary_period
 * @property string|null $salary_raw
 * @property ContractType $contract_type
 * @property Seniority $seniority
 * @property CarbonImmutable $posted_at
 * @property CarbonImmutable $fetched_at
 * @property CarbonImmutable|null $expires_at
 * @property string $attribution
 * @property string $fingerprint
 * @property CarbonImmutable|null $notified_at
 * @property CarbonImmutable|null $seen_at
 * @property JobState|null $state
 * @property CarbonImmutable|null $state_at
 * @property CarbonImmutable $created_at
 */
#[Fillable([
    'source', 'source_job_id', 'source_url', 'apply_url', 'title', 'company', 'description', 'tags', 'work_mode',
    'eligibility_type', 'eligibility_regions', 'eligibility_countries', 'office_location', 'timezone_min', 'timezone_max',
    'salary_min', 'salary_max', 'salary_currency', 'salary_period', 'salary_raw', 'contract_type', 'seniority',
    'posted_at', 'fetched_at', 'expires_at', 'attribution', 'fingerprint', 'notified_at', 'seen_at', 'state', 'state_at',
])]
class Job extends Model
{
    /**
     * The columns of one normalised job. Leaves out notified_at and the state columns, so an update keeps them.
     *
     * @return array<string, mixed>
     */
    public static function attributesOf(NormalisedJob $job): array
    {
        return [
            'source' => $job->source,
            'source_job_id' => $job->sourceJobId,
            'source_url' => $job->sourceUrl,
            'apply_url' => $job->applyUrl,
            'title' => $job->title,
            'company' => $job->company,
            'description' => $job->description,
            'tags' => $job->tags,
            'work_mode' => $job->workMode,
            'eligibility_type' => $job->eligibility->type->value,
            'eligibility_regions' => $job->eligibility->regions,
            'eligibility_countries' => $job->eligibility->countries,
            'office_location' => $job->officeLocation,
            'timezone_min' => $job->timezoneMin,
            'timezone_max' => $job->timezoneMax,
            'salary_min' => $job->salary?->min,
            'salary_max' => $job->salary?->max,
            'salary_currency' => $job->salary?->currency,
            'salary_period' => $job->salary?->period,
            'salary_raw' => $job->salary?->raw,
            'contract_type' => $job->contractType,
            'seniority' => $job->seniority,
            'posted_at' => $job->postedAt,
            'fetched_at' => $job->fetchedAt,
            'expires_at' => $job->expiresAt,
            'attribution' => $job->attribution,
            'fingerprint' => $job->fingerprint,
        ];
    }

    public function toNormalised(): NormalisedJob
    {
        return new NormalisedJob(
            source: $this->source,
            sourceJobId: $this->source_job_id,
            sourceUrl: $this->source_url,
            applyUrl: $this->apply_url,
            title: $this->title,
            company: $this->company,
            description: $this->description,
            tags: $this->tags,
            workMode: $this->work_mode,
            eligibility: Eligibility::fromArray([
                'type' => $this->eligibility_type,
                'regions' => $this->eligibility_regions,
                'countries' => $this->eligibility_countries,
            ]),
            officeLocation: $this->office_location,
            timezoneMin: $this->timezone_min,
            timezoneMax: $this->timezone_max,
            salary: $this->salary_currency === null || $this->salary_period === null || $this->salary_raw === null ? null : new Salary(
                $this->salary_min,
                $this->salary_max,
                $this->salary_currency,
                $this->salary_period,
                $this->salary_raw,
            ),
            contractType: $this->contract_type,
            seniority: $this->seniority,
            postedAt: $this->posted_at,
            fetchedAt: $this->fetched_at,
            expiresAt: $this->expires_at,
            attribution: $this->attribution,
        );
    }

    /**
     * @return HasMany<AlsoOnSource, $this>
     */
    public function alsoOn(): HasMany
    {
        return $this->hasMany(AlsoOnSource::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'work_mode' => WorkMode::class,
            'eligibility_regions' => 'array',
            'eligibility_countries' => 'array',
            'timezone_min' => 'float',
            'timezone_max' => 'float',
            'salary_min' => 'float',
            'salary_max' => 'float',
            'salary_period' => SalaryPeriod::class,
            'contract_type' => ContractType::class,
            'seniority' => Seniority::class,
            'posted_at' => 'immutable_datetime',
            'fetched_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'notified_at' => 'immutable_datetime',
            'seen_at' => 'immutable_datetime',
            'state' => JobState::class,
            'state_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
