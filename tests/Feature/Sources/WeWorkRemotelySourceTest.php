<?php

use App\Sources\JobSource;
use App\Sources\SkipItem;
use App\Sources\WeWorkRemotely\WeWorkRemotelySource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-10-29T10:00:00Z');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * @return list<array<string, string>>
 */
function wwrItems(): array
{
    Http::fake(['weworkremotely.com/*' => Http::response(JobFields::fixture('weworkremotely/back-end.rss'), 200, ['Content-Type' => 'application/rss+xml'])]);

    return iterator_to_array((new WeWorkRemotelySource)->fetch(), false);
}

function wwrItem(int $index = 0, array $changes = []): array
{
    return array_merge(wwrItems()[$index], $changes);
}

it('is a job source with its key, attribution and interval', function () {
    $source = new WeWorkRemotelySource;

    expect($source)->toBeInstanceOf(JobSource::class)
        ->and($source->key())->toBe('weworkremotely')
        ->and($source->attribution())->toBe('via We Work Remotely')
        ->and($source->minInterval()->totalHours)->toEqual(2);
});

it('reads the items of the back-end RSS feed', function () {
    expect(wwrItems())->toHaveCount(3);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://weworkremotely.com/categories/remote-back-end-programming-jobs.rss');
});

it('normalises an item field by field', function () {
    $job = (new WeWorkRemotelySource)->normalise(wwrItem(0));

    expect(JobFields::of($job))->toBe([
        'source' => 'weworkremotely',
        'sourceJobId' => 'https://weworkremotely.com/remote-jobs/twikey-senior-java-developer',
        'sourceUrl' => 'https://weworkremotely.com/remote-jobs/twikey-senior-java-developer',
        'applyUrl' => 'https://weworkremotely.com/remote-jobs/twikey-senior-java-developer',
        'title' => 'Senior Java Developer',
        'company' => 'Twikey',
        'tags' => ['java', 'postgresql', 'spring', 'back-end programming'],
        'workMode' => 'remote',
        // The country list needs the country table (M4).
        'eligibility' => ['type' => 'unknown', 'regions' => [], 'countries' => []],
        'officeLocation' => null,
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => null,
        'contractType' => 'full_time',
        'seniority' => 'unknown',
        'postedAt' => '2026-09-17T10:51:23Z',
        'fetchedAt' => '2026-10-29T10:00:00Z',
        'expiresAt' => '2026-11-16T10:51:23Z',
        'attribution' => 'via We Work Remotely',
    ]);

    expect($job->description)
        ->toStartWith("Headquarters: Ghent - Belgium\n\nURL: https://www.twikey.com/jobs")
        ->not->toContain('<p>')
        ->not->toContain('&lt;');
});

it('splits the title at the first ": " only', function () {
    $job = (new WeWorkRemotelySource)->normalise(wwrItem(0, ['title' => 'Acme: Engineer: Payments: EU']));

    expect($job->company)->toBe('Acme')
        ->and($job->title)->toBe('Engineer: Payments: EU');
});

it('gives worldwide for an empty country and "Anywhere in the World"', function () {
    expect((new WeWorkRemotelySource)->normalise(wwrItem(1))->eligibility->type->value)->toBe('worldwide');
});

it('gives unknown eligibility for a region other than "Anywhere in the World"', function () {
    expect((new WeWorkRemotelySource)->normalise(wwrItem(1, ['region' => 'Europe Only']))->eligibility->type->value)->toBe('unknown');
});

it('reads "and" lists of skills as tags', function () {
    expect((new WeWorkRemotelySource)->normalise(wwrItem(1))->tags)->toBe(['django', 'python', 'back-end programming']);
});

it('maps the type to the contract type', function (string $type, string $contract) {
    expect((new WeWorkRemotelySource)->normalise(wwrItem(0, ['type' => $type]))->contractType->value)->toBe($contract);
})->with([
    ['Full-Time', 'full_time'],
    ['Part-Time', 'part_time'],
    ['Contract', 'contract'],
    ['Internship', 'unknown'],
]);

it('gives no expiry date when the item has none', function () {
    expect((new WeWorkRemotelySource)->normalise(wwrItem(0, ['expires_at' => '']))->expiresAt)->toBeNull();
});

it('fails when the feed does not answer', function () {
    Http::fake(['weworkremotely.com/*' => Http::response('', 503)]);

    iterator_to_array((new WeWorkRemotelySource)->fetch());
})->throws(RequestException::class);

it('fails when the feed is not RSS', function () {
    Http::fake(['weworkremotely.com/*' => Http::response('<html><body>Not found</body></html>')]);

    iterator_to_array((new WeWorkRemotelySource)->fetch());
})->throws(RuntimeException::class, 'The We Work Remotely feed is not RSS.');

it('skips an item that cannot be normalised', function (mixed $item) {
    (new WeWorkRemotelySource)->normalise($item);
})->throws(SkipItem::class)->with([
    'not an array' => ['text'],
    'no link' => [fn () => wwrItem(0, ['link' => ''])],
    'no company in the title' => [fn () => wwrItem(0, ['title' => 'Senior Java Developer'])],
    'no title after the company' => [fn () => wwrItem(0, ['title' => 'Twikey: '])],
    'no date' => [fn () => wwrItem(0, ['pubDate' => ''])],
    'a bad date' => [fn () => wwrItem(0, ['pubDate' => '2026-09-17'])],
    'a bad expiry date' => [fn () => wwrItem(0, ['expires_at' => 'next month'])],
]);
