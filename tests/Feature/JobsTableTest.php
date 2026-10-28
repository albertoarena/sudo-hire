<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the jobs table with the schema columns', function () {
    expect(Schema::hasColumns('jobs', [
        'id',
        'source',
        'source_job_id',
        'source_url',
        'apply_url',
        'title',
        'company',
        'description',
        'tags',
        'work_mode',
        'eligibility_type',
        'eligibility_regions',
        'eligibility_countries',
        'office_location',
        'timezone_min',
        'timezone_max',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_period',
        'salary_raw',
        'contract_type',
        'seniority',
        'posted_at',
        'fetched_at',
        'expires_at',
        'attribution',
        'fingerprint',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('keeps one row per source job', function () {
    $indexes = collect(Schema::getIndexes('jobs'));

    expect($indexes->contains(fn ($index) => $index['unique'] && $index['columns'] === ['source', 'source_job_id']))->toBeTrue()
        ->and($indexes->contains(fn ($index) => $index['columns'] === ['fingerprint']))->toBeTrue();
});

it('moves the Laravel queue table out of the way', function () {
    expect(config('queue.connections.database.table'))->toBe('queue_jobs')
        ->and(Schema::hasTable('queue_jobs'))->toBeTrue();
});
