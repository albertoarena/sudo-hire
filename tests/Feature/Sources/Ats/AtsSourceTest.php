<?php

use App\Sources\Ats\AtsItem;
use App\Sources\Ats\AtsSource;
use App\Sources\Ats\CompanyEntry;
use App\Sources\JobSource;
use App\Sources\NormaliseItems;
use App\Sources\RunErrorKind;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\InMemoryCompanyList;
use Tests\Support\JobFields;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-10-29T10:00:00Z');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * @param  list<string>  $entries
 */
function companyList(array $entries): InMemoryCompanyList
{
    return new InMemoryCompanyList(array_map(fn (string $entry) => CompanyEntry::parse($entry), $entries));
}

function fakeAtsResponses(array $overrides = []): void
{
    Http::fake(array_merge([
        'boards-api.greenhouse.io/v1/boards/gitlab/*' => Http::response(JobFields::fixture('greenhouse/jobs.json')),
        'api.lever.co/v0/postings/spotify*' => Http::response(JobFields::fixture('lever/postings.json')),
        'api.ashbyhq.com/posting-api/job-board/ashby*' => Http::response(JobFields::fixture('ashby/job-board.json')),
        'ottonova.jobs.personio.de/*' => Http::response(JobFields::fixture('personio/positions.xml')),
        'empty.jobs.personio.de/*' => Http::response(JobFields::fixture('personio/positions-empty.xml')),
    ], $overrides));
}

/**
 * @return list<string>
 */
function fetchedCompanies(JobSource $source): array
{
    return array_map(fn (AtsItem $item) => $item->company->key(), iterator_to_array($source->fetch(), false));
}

it('is a job source with the key ats', function () {
    $source = new AtsSource(companyList([]));

    expect($source)->toBeInstanceOf(JobSource::class)
        ->and($source->key())->toBe('ats')
        ->and($source->attribution())->toBe('via company careers')
        ->and($source->minInterval()->totalHours)->toEqual(6);
});

it('reads every company and its items carry the company entry', function () {
    fakeAtsResponses();
    $companies = companyList(['greenhouse:gitlab', 'lever:spotify', 'ashby:ashby', 'personio:ottonova']);

    $items = iterator_to_array((new AtsSource($companies))->fetch(), false);

    expect(array_count_values(array_map(fn (AtsItem $item) => $item->company->key(), $items)))->toBe([
        'greenhouse:gitlab' => 3,
        'lever:spotify' => 3,
        'ashby:ashby' => 3,
        'personio:ottonova' => 3,
    ])
        ->and($items[0]->fetchedAt->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z')
        ->and($companies->jobCounts)->toBe([
            'greenhouse:gitlab' => 3,
            'lever:spotify' => 3,
            'ashby:ashby' => 3,
            'personio:ottonova' => 3,
        ])
        ->and($companies->errors)->toBe([]);
});

it('normalises the items of each ATS', function () {
    fakeAtsResponses();
    $source = new AtsSource(companyList(['greenhouse:gitlab', 'lever:spotify', 'ashby:ashby', 'personio:ottonova']));

    $batch = (new NormaliseItems)($source);

    expect($batch->skipped)->toBe(0)
        ->and(array_map(fn ($job) => explode(':', $job->sourceJobId)[0], $batch->jobs))
        ->toBe(['greenhouse', 'greenhouse', 'greenhouse', 'lever', 'lever', 'lever', 'ashby', 'ashby', 'ashby', 'personio', 'personio', 'personio']);
});

it('stores the error of a failing company and still reads the next one', function (string $entry, Closure $response, string $error, RunErrorKind $kind) {
    fakeAtsResponses($response());
    $companies = companyList([$entry, 'lever:spotify']);

    expect(fetchedCompanies(new AtsSource($companies)))->toBe(['lever:spotify', 'lever:spotify', 'lever:spotify'])
        ->and($companies->errors)->toBe([$entry => $error])
        ->and($companies->errorKinds)->toBe([$entry => $kind])
        ->and($companies->jobCounts)->toBe(['lever:spotify' => 3])
        ->and($companies->checkedAt[$entry]->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z');
})->with([
    '404' => ['greenhouse:nobody', fn () => ['boards-api.greenhouse.io/v1/boards/nobody/*' => Http::response('{"status":404,"error":"Job not found"}', 404)], 'HTTP 404', RunErrorKind::Http],
    'HTML instead of XML' => ['personio:nobody', fn () => ['nobody.jobs.personio.de/*' => Http::response('<!DOCTYPE html><html><body>Not found</body></html>', 200)], 'The response is not Personio XML.', RunErrorKind::Parse],
    'Personio 404 HTML' => ['personio:nobody', fn () => ['nobody.jobs.personio.de/*' => Http::response('<!DOCTYPE html><html><body>Not found</body></html>', 404)], 'HTTP 404', RunErrorKind::Http],
    'timeout' => ['ashby:slow', fn () => ['api.ashbyhq.com/posting-api/job-board/slow*' => Http::failedConnection('cURL error 28: Operation timed out')], 'Connection failed: cURL error 28: Operation timed out', RunErrorKind::Http],
    'certificate' => ['greenhouse:tls', fn () => ['boards-api.greenhouse.io/v1/boards/tls/*' => Http::failedConnection('cURL error 60: SSL certificate problem: unable to get local issuer certificate')], 'Connection failed: cURL error 60: SSL certificate problem: unable to get local issuer certificate', RunErrorKind::Certificate],
    'bad JSON' => ['greenhouse:broken', fn () => ['boards-api.greenhouse.io/v1/boards/broken/*' => Http::response('{"jobs": [', 200)], 'The response is not valid JSON.', RunErrorKind::Parse],
    'JSON with no job list' => ['ashby:odd', fn () => ['api.ashbyhq.com/posting-api/job-board/odd*' => Http::response('{"apiVersion":"1"}', 200)], 'The response has no job list.', RunErrorKind::Parse],
]);

it('gives 0 items and no error for a Personio company with no positions', function () {
    fakeAtsResponses();
    $companies = companyList(['personio:empty']);

    expect(fetchedCompanies(new AtsSource($companies)))->toBe([])
        ->and($companies->jobCounts)->toBe(['personio:empty' => 0])
        ->and($companies->errors)->toBe([]);
});

it('does not read a company again within 6 hours', function () {
    fakeAtsResponses();
    $companies = companyList(['greenhouse:gitlab', 'lever:spotify']);
    $companies->checkedAt['greenhouse:gitlab'] = CarbonImmutable::parse('2026-10-29T04:00:01Z');

    expect(fetchedCompanies(new AtsSource($companies)))->toBe(['lever:spotify', 'lever:spotify', 'lever:spotify']);

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'greenhouse'));
});

it('reads a company again after 6 hours', function () {
    fakeAtsResponses();
    $companies = companyList(['greenhouse:gitlab']);
    $companies->checkedAt['greenhouse:gitlab'] = CarbonImmutable::parse('2026-10-29T04:00:00Z');

    expect(fetchedCompanies(new AtsSource($companies)))->toHaveCount(3);
});

it('waits 6 hours after a failed read too', function () {
    fakeAtsResponses(['boards-api.greenhouse.io/v1/boards/nobody/*' => Http::response('', 404)]);
    $companies = companyList(['greenhouse:nobody']);
    $source = new AtsSource($companies);

    fetchedCompanies($source);
    fetchedCompanies($source);

    Http::assertSentCount(1);
});

it('skips a raw item that is not an ATS item', function () {
    (new AtsSource(companyList([])))->normalise(['id' => 1]);
})->throws(SkipItem::class);
