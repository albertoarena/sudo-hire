<?php

use App\Models\AtsCompany;
use App\Sources\Ats\Ats;
use App\Sources\Ats\AtsSource;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\CompanyList;
use App\Sources\Ats\DatabaseCompanyList;
use App\Sources\RunErrorKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\JobFields;

uses(RefreshDatabase::class);

it('creates the ats_companies table', function () {
    expect(Schema::hasColumns('ats_companies', [
        'id', 'ats', 'slug', 'display_name', 'enabled',
        'last_checked_at', 'last_job_count', 'last_error', 'last_error_kind',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('allows one row for each ATS and slug', function () {
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify']);
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify again']);
})->throws(UniqueConstraintViolationException::class);

it('allows the same slug on two ATS', function () {
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'acme', 'display_name' => 'Acme']);
    AtsCompany::create(['ats' => Ats::Ashby, 'slug' => 'acme', 'display_name' => 'Acme']);

    expect(AtsCompany::count())->toBe(2);
});

it('is the company list in the container', function () {
    expect(app(CompanyList::class))->toBeInstanceOf(DatabaseCompanyList::class);
});

it('lists the enabled companies with their display names', function () {
    AtsCompany::create(['ats' => Ats::Greenhouse, 'slug' => 'gitlab', 'display_name' => 'GitLab']);
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify', 'enabled' => false]);
    AtsCompany::create(['ats' => Ats::Personio, 'slug' => 'ottonova', 'display_name' => 'ottonova']);

    $companies = (new DatabaseCompanyList)->companies();

    expect(array_map(fn (CompanyEntry $entry) => [$entry->key(), $entry->displayName], $companies))
        ->toBe([['greenhouse:gitlab', 'GitLab'], ['personio:ottonova', 'ottonova']]);
});

it('is enabled by default', function () {
    $company = AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify']);

    expect($company->fresh()?->enabled)->toBeTrue();
});

it('stores the job count of a read and clears the last error', function () {
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify', 'last_error' => 'HTTP 500', 'last_error_kind' => 'http']);
    $list = new DatabaseCompanyList;
    $entry = CompanyEntry::parse('lever:spotify');

    $list->recordJobs($entry, 3, CarbonImmutable::parse('2026-10-29T10:00:00Z'));

    $company = AtsCompany::sole();

    expect($company->last_job_count)->toBe(3)
        ->and($company->last_error)->toBeNull()
        ->and($company->last_error_kind)->toBeNull()
        ->and($company->last_checked_at?->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z')
        ->and($list->lastCheckedAt($entry)?->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z');
});

it('stores the error of a read and clears the last job count', function () {
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify', 'last_job_count' => 4]);

    (new DatabaseCompanyList)->recordError(CompanyEntry::parse('lever:spotify'), 'HTTP 404', RunErrorKind::Http, CarbonImmutable::parse('2026-10-29T10:00:00Z'));

    $company = AtsCompany::sole();

    expect($company->last_error)->toBe('HTTP 404')
        ->and($company->last_error_kind)->toBe(RunErrorKind::Http)
        ->and($company->last_job_count)->toBeNull()
        ->and($company->last_checked_at?->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z');
});

it('has no last check for a company that was never read', function () {
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify']);

    expect((new DatabaseCompanyList)->lastCheckedAt(CompanyEntry::parse('lever:spotify')))->toBeNull();
});

it('stores the certificate error kind of one company and still reads the others', function () {
    CarbonImmutable::setTestNow('2026-10-29T10:00:00Z');
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/gitlab/*' => Http::failedConnection('cURL error 60: SSL certificate problem: unable to get local issuer certificate'),
        'api.lever.co/v0/postings/spotify*' => Http::response(JobFields::fixture('lever/postings.json')),
    ]);
    AtsCompany::create(['ats' => Ats::Greenhouse, 'slug' => 'gitlab', 'display_name' => 'GitLab']);
    AtsCompany::create(['ats' => Ats::Lever, 'slug' => 'spotify', 'display_name' => 'Spotify']);

    $items = iterator_to_array((new AtsSource(new DatabaseCompanyList))->fetch(), false);

    $gitlab = AtsCompany::query()->where('slug', 'gitlab')->sole();
    $spotify = AtsCompany::query()->where('slug', 'spotify')->sole();

    expect($items)->toHaveCount(3)
        ->and($gitlab->last_error_kind)->toBe(RunErrorKind::Certificate)
        ->and($gitlab->last_error)->toContain('cURL error 60')
        ->and($spotify->last_error_kind)->toBeNull()
        ->and($spotify->last_job_count)->toBe(3);

    CarbonImmutable::setTestNow();
});
