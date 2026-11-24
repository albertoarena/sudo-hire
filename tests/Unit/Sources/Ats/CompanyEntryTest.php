<?php

use App\Sources\Ats\Ats;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\InvalidCompanyEntry;

it('parses an entry in the form <ats>:<slug>', function () {
    $entry = CompanyEntry::parse('greenhouse:gitlab');

    expect($entry->ats)->toBe(Ats::Greenhouse)
        ->and($entry->slug)->toBe('gitlab')
        ->and($entry->displayName)->toBe('Gitlab')
        ->and($entry->key())->toBe('greenhouse:gitlab');
});

it('accepts every supported ATS', function (string $ats) {
    expect(CompanyEntry::parse("{$ats}:acme")->ats->value)->toBe($ats);
})->with(['greenhouse', 'lever', 'ashby', 'personio', 'workable']);

it('ignores spaces and the case of the ATS name', function () {
    $entry = CompanyEntry::parse('  Lever:spotify ');

    expect($entry->ats)->toBe(Ats::Lever)
        ->and($entry->slug)->toBe('spotify');
});

it('gives the slug in title case as the default display name', function (string $slug, string $name) {
    expect(CompanyEntry::parse("lever:{$slug}")->displayName)->toBe($name);
})->with([
    ['spotify', 'Spotify'],
    ['acme-corp', 'Acme Corp'],
    ['big_data_co', 'Big Data Co'],
]);

it('leaves a domain ending out of the default display name', function (string $slug, string $name) {
    $entry = CompanyEntry::parse("ashby:{$slug}");

    expect($entry->displayName)->toBe($name)
        ->and($entry->slug)->toBe($slug);
})->with([
    'a real Ashby slug with .com' => ['jimdo.com', 'Jimdo'],
    'a second real Ashby slug with .com' => ['kraken.com', 'Kraken'],
    '.io' => ['acme.io', 'Acme'],
    'a dot that is not a domain ending' => ['acme.labs', 'Acme.labs'],
    'the ending alone is not a name' => ['com', 'Com'],
]);

it('keeps a display name that is given', function () {
    expect(CompanyEntry::parse('lever:spotify', 'Spotify AB')->displayName)->toBe('Spotify AB');
});

it('rejects a bad entry', function (string $entry) {
    CompanyEntry::parse($entry);
})->throws(InvalidCompanyEntry::class)->with([
    'unknown ATS' => 'workday:acme',
    'empty slug' => 'greenhouse:',
    'blank slug' => 'greenhouse:   ',
    'no colon' => 'greenhouse',
    'empty ATS' => ':acme',
    'slash in the slug' => 'personio:acme/evil',
    'dot host in the slug' => 'personio:evil.example.com#',
]);
