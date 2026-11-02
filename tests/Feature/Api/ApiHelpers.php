<?php

namespace Tests\Feature\Api;

use App\Models\Job;
use App\Models\StoredSettings;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Tests\Unit\Filters\Make;

/**
 * Builds stored settings and jobs for the API tests.
 */
final class ApiHelpers
{
    /**
     * @param  array<string, mixed>  $overrides  values in the shape of Settings::toArray()
     */
    public static function settings(array $overrides = []): void
    {
        StoredSettings::query()->delete();
        StoredSettings::query()->create(['data' => Make::settings(array_merge(['country' => 'IT'], $overrides))->toArray()]);
    }

    /**
     * Stores one job, open worldwide, posted and fetched now. Null when it is stored as "also on".
     *
     * @param  array<string, mixed>  $overrides  NormalisedJob arguments
     */
    public static function job(string $id, array $overrides = []): ?Job
    {
        $job = new NormalisedJob(...jobAttributes(array_merge([
            'sourceJobId' => $id,
            'title' => "Laravel Developer {$id}",
            'eligibility' => Eligibility::worldwide(),
            'postedAt' => CarbonImmutable::now(),
            'fetchedAt' => CarbonImmutable::now(),
        ], $overrides)));

        app(JobStore::class)->save([$job]);

        return Job::query()->where('source', $job->source)->where('source_job_id', $id)->first();
    }
}
