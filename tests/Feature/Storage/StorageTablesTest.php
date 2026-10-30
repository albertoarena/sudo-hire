<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('stores when a job was notified', function () {
    expect(Schema::hasColumn('jobs', 'notified_at'))->toBeTrue();
});

it('creates the "also on" table with one row per source job', function () {
    expect(Schema::hasColumns('job_sources', [
        'id', 'job_id', 'source', 'source_job_id', 'source_url', 'apply_url', 'attribution', 'fetched_at', 'created_at', 'updated_at',
    ]))->toBeTrue();

    $indexes = collect(Schema::getIndexes('job_sources'));

    expect($indexes->contains(fn ($index) => $index['unique'] && $index['columns'] === ['source', 'source_job_id']))->toBeTrue();
});

it('creates the source runs table', function () {
    expect(Schema::hasColumns('source_runs', [
        'id', 'source', 'started_at', 'finished_at', 'error', 'error_kind', 'items_fetched', 'items_skipped',
        'unknown_eligibility_share', 'unknown_salary_share', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('creates the settings table', function () {
    expect(Schema::hasColumns('settings', ['id', 'data', 'created_at', 'updated_at']))->toBeTrue();
});
