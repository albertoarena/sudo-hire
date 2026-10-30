<?php

use App\Sources\Ats\AtsItem;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\Readers\PersonioReader;
use App\Sources\Ats\ReadFailed;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

const PERSONIO_404_HTML = '<!DOCTYPE html><html><head><title>Page not found</title></head><body><h1>404</h1></body></html>';

beforeEach(function () {
    $this->company = CompanyEntry::parse('personio:ottonova', 'ottonova');
    $this->fetchedAt = CarbonImmutable::parse('2026-10-29T10:00:00Z');
});

/**
 * @return list<array<mixed>>
 */
function personioItems(CompanyEntry $company): array
{
    Http::fake(['*.jobs.personio.de/*' => Http::response(JobFields::fixture('personio/positions.xml'), 200, ['Content-Type' => 'text/xml'])]);

    return (new PersonioReader)->read($company);
}

it('reads the positions from the XML feed', function () {
    $items = personioItems($this->company);

    expect($items)->toHaveCount(3)
        ->and($items[0]['id'])->toBe('2807356');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://ottonova.jobs.personio.de/xml');
});

it('normalises a position field by field', function () {
    $job = (new PersonioReader)->normalise(new AtsItem($this->company, personioItems($this->company)[0], $this->fetchedAt));

    expect(JobFields::of($job))->toBe([
        'source' => 'ats',
        'sourceJobId' => 'personio:ottonova:2807356',
        'sourceUrl' => 'https://ottonova.jobs.personio.de/job/2807356',
        'applyUrl' => 'https://ottonova.jobs.personio.de/job/2807356',
        'title' => 'Head of Finance & Controlling (m/w/d)',
        'company' => 'ottonova',
        'tags' => ['finance'],
        'workMode' => 'unknown',
        'eligibility' => ['type' => 'unknown', 'regions' => [], 'countries' => []],
        'officeLocation' => 'München',
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => null,
        'contractType' => 'full_time',
        // "experienced" gives no level: the title "Head of" gives it.
        'seniority' => 'lead',
        'postedAt' => '2026-09-22T11:03:32Z',
        'fetchedAt' => '2026-10-29T10:00:00Z',
        'expiresAt' => null,
        'attribution' => 'via ottonova careers',
    ]);
});

it('joins the description parts as "name: value" and skips the URL part', function () {
    $job = (new PersonioReader)->normalise(new AtsItem($this->company, personioItems($this->company)[1], $this->fetchedAt));

    expect($job->description)
        ->toStartWith('DEIN TEAM: ottonova ist Deutschlands erste digitale private Krankenversicherung.')
        ->toContain("\n\nDEIN PROFIL: Grundlage deines Profils bildet ein Studienabschluss")
        ->toContain("\n\nHaben wir Dein Interesse geweckt?: Wenn du Leidenschaft für KI hast")
        ->not->toContain('URL:')
        ->not->toContain('ai-engineer')
        ->not->toContain('<strong>');
});

it('takes the company name from the company entry', function () {
    $company = CompanyEntry::parse('personio:ottonova');

    $job = (new PersonioReader)->normalise(new AtsItem($company, personioItems($company)[0], $this->fetchedAt));

    expect($job->company)->toBe('Ottonova')
        ->and($job->attribution)->toBe('via Ottonova careers');
});

it('gives the keywords and the department as tags', function () {
    $job = (new PersonioReader)->normalise(new AtsItem($this->company, personioItems($this->company)[1], $this->fetchedAt));

    expect($job->tags)->toBe(['ai engineering', 'ai platforms', 'ai architecture', 'ai use cases', 'data & analytics', 'genai']);
});

it('reads the title of each position', function (int $index, string $contract, string $seniority) {
    $job = (new PersonioReader)->normalise(new AtsItem($this->company, personioItems($this->company)[$index], $this->fetchedAt));

    expect(JobFields::parsed($job))->toBe(JobFields::expected([
        'officeLocation' => 'München',
        'contractType' => $contract,
        'seniority' => $seniority,
    ]));
})->with([
    'head of' => [0, 'full_time', 'lead'],
    'senior' => [1, 'full_time', 'senior'],
    // The seniority field says "student": it wins over the title.
    'student' => [2, 'unknown', 'junior'],
]);

it('reads the work mode from the title', function () {
    $item = array_merge(personioItems($this->company)[1], ['name' => 'Senior AI Engineer (Remote)']);

    expect((new PersonioReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt))->workMode->value)->toBe('remote');
});

it('maps an intern student job', function () {
    $job = (new PersonioReader)->normalise(new AtsItem($this->company, personioItems($this->company)[2], $this->fetchedAt));

    expect($job->contractType->value)->toBe('unknown')
        ->and($job->seniority->value)->toBe('junior');
});

it('maps the employment type and the schedule to the contract type', function (string $type, string $schedule, string $contract) {
    $item = array_merge(personioItems($this->company)[0], ['employmentType' => $type, 'schedule' => $schedule]);

    expect((new PersonioReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt))->contractType->value)->toBe($contract);
})->with([
    ['permanent', 'full-time', 'full_time'],
    ['permanent', 'part-time', 'part_time'],
    ['permanent', '', 'unknown'],
    ['freelance', 'full-time', 'contract'],
    ['temporary', 'part-time', 'contract'],
    ['intern', 'full-time', 'unknown'],
]);

it('maps the seniority', function (string $seniority, string $expected) {
    $item = array_merge(personioItems($this->company)[0], ['seniority' => $seniority]);

    expect((new PersonioReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt))->seniority->value)->toBe($expected);
})->with([
    ['entry-level', 'junior'],
    ['student', 'junior'],
    // No level: the title "Head of Finance" gives it.
    ['experienced', 'lead'],
    ['senior', 'senior'],
    ['lead', 'lead'],
]);

it('adds the additional offices to the office location', function () {
    $xml = str_replace(
        '<office>München</office>',
        '<office>München</office><additionalOffices><office>Berlin</office><office>Köln</office></additionalOffices>',
        JobFields::fixture('personio/positions.xml'),
    );
    Http::fake(['*.jobs.personio.de/*' => Http::response($xml)]);

    $item = (new PersonioReader)->read($this->company)[0];

    expect((new PersonioReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt))->officeLocation)
        ->toBe('München; Berlin; Köln');
});

it('gives no items and no error for a company with no positions', function () {
    Http::fake(['*.jobs.personio.de/*' => Http::response(JobFields::fixture('personio/positions-empty.xml'))]);

    expect((new PersonioReader)->read($this->company))->toBe([]);
});

it('fails for an unknown slug that returns a 404 HTML page', function () {
    Http::fake(['*.jobs.personio.de/*' => Http::response(PERSONIO_404_HTML, 404, ['Content-Type' => 'text/html'])]);

    (new PersonioReader)->read($this->company);
})->throws(ReadFailed::class, 'HTTP 404');

it('fails for HTML with status 200', function () {
    Http::fake(['*.jobs.personio.de/*' => Http::response(PERSONIO_404_HTML, 200, ['Content-Type' => 'text/html'])]);

    (new PersonioReader)->read($this->company);
})->throws(ReadFailed::class, 'The response is not Personio XML.');

it('skips a position that cannot be normalised', function (array $changes) {
    $item = array_merge(personioItems($this->company)[0], $changes);

    (new PersonioReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt));
})->throws(SkipItem::class)->with([
    'no title' => [['name' => '']],
    'no id' => [['id' => null]],
    'an id that is not a number' => [['id' => '../evil']],
    'no date' => [['createdAt' => null]],
]);
