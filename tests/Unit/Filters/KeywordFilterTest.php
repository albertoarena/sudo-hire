<?php

use App\Filters\KeywordFilter;
use Tests\Unit\Filters\Make;

const RAILS_TITLE = 'Tech Lead Full-Stack Rails Engineer';
const RAILS_TAGS = ['api', 'CSS', 'docker', 'elasticsearch', 'fullstack', 'ruby/rails', 'AI/ML', 'Micro services'];
const LARAVEL_TITLE = "Senior Fullstack PHP/Laravel Developer \u{2013} Remote (Europe/Berlin Time Zone)";
const BACKEND_TITLE = 'Senior back-end Engineer';
const BACKEND_TAGS = ['C#', 'C++', 'node.js', 'data science', 'Typescript '];
const OTE_TEXT = 'Sales positions generally offer a competitive On Target Earnings (OTE) incentive structure in addition to base salary.';

it('uses real strings from the fixtures', function (string $file, string $text) {
    expect(Make::inFixture($file, $text))->toBeTrue("\"{$text}\" is not in {$file}");
})->with([
    ['remotive/remote-jobs.json', RAILS_TITLE],
    ...array_map(fn (string $tag) => ['remotive/remote-jobs.json', $tag], [...RAILS_TAGS, ...BACKEND_TAGS]),
    ['larajobs/feed.xml', LARAVEL_TITLE],
    ['remotive/remote-jobs.json', BACKEND_TITLE],
    ['weworkremotely/back-end.rss', OTE_TEXT],
]);

it('passes a job that has an include keyword in the title or the tags', function (string $title, array $tags, array $include, bool $passes) {
    $job = Make::job(['title' => $title, 'tags' => $tags]);

    expect(KeywordFilter::passesInclude($job, Make::settings(['keywordsInclude' => $include])))->toBe($passes);
})->with([
    'empty list lets every job pass' => [RAILS_TITLE, RAILS_TAGS, [], true],
    'word in the title' => [RAILS_TITLE, RAILS_TAGS, ['rails'], true],
    'other case' => [RAILS_TITLE, RAILS_TAGS, ['RAILS'], true],
    'part of a word does not match' => [RAILS_TITLE, RAILS_TAGS, ['rail'], false],
    'hyphenated word' => [RAILS_TITLE, RAILS_TAGS, ['full-stack'], true],
    'one word of a hyphenated word' => [RAILS_TITLE, RAILS_TAGS, ['stack'], true],
    'word next to a slash' => [LARAVEL_TITLE, [], ['laravel'], true],
    'any word of the list' => [LARAVEL_TITLE, [], ['symfony', 'php'], true],
    'no word of the list' => [LARAVEL_TITLE, [], ['symfony', 'django'], false],
    'word in a tag' => [RAILS_TITLE, RAILS_TAGS, ['docker'], true],
    'word in a tag with a slash' => [RAILS_TITLE, RAILS_TAGS, ['ruby'], true],
    'two words in a tag' => [BACKEND_TITLE, BACKEND_TAGS, ['data science'], true],
    'tag with symbols' => [BACKEND_TITLE, BACKEND_TAGS, ['c++'], true],
    'tag with a dot' => [BACKEND_TITLE, BACKEND_TAGS, ['node.js'], true],
    'tag with spaces after it' => [BACKEND_TITLE, BACKEND_TAGS, ['typescript'], true],
    '"c" does not match "c#" or "c++"' => [BACKEND_TITLE, BACKEND_TAGS, ['c'], false],
    'part of a tag word does not match' => [RAILS_TITLE, RAILS_TAGS, ['elastic'], false],
]);

it('fails a job that has an exclude keyword in the title or the tags', function (string $title, array $tags, array $exclude, bool $passes) {
    $job = Make::job(['title' => $title, 'tags' => $tags]);

    expect(KeywordFilter::passesExclude($job, Make::settings(['keywordsExclude' => $exclude])))->toBe($passes);
})->with([
    'empty list' => [RAILS_TITLE, RAILS_TAGS, [], true],
    'word in the title' => [RAILS_TITLE, RAILS_TAGS, ['lead'], false],
    'other case' => [RAILS_TITLE, RAILS_TAGS, ['LEAD'], false],
    'part of a word does not match' => [RAILS_TITLE, RAILS_TAGS, ['engine'], true],
    'word in a tag' => [BACKEND_TITLE, BACKEND_TAGS, ['c#'], false],
    'no word of the list' => [BACKEND_TITLE, BACKEND_TAGS, ['java', 'golang'], true],
]);

it('reads the description only when the setting is on', function (bool $matchDescription, bool $includePasses, bool $excludePasses) {
    $job = Make::job(['title' => BACKEND_TITLE, 'description' => OTE_TEXT]);
    $settings = Make::settings(['keywordsInclude' => ['incentive'], 'keywordsExclude' => ['sales'], 'matchDescription' => $matchDescription]);

    expect(KeywordFilter::passesInclude($job, $settings))->toBe($includePasses)
        ->and(KeywordFilter::passesExclude($job, $settings))->toBe($excludePasses);
})->with([
    'off' => [false, false, true],
    'on' => [true, true, false],
]);

it('passes a job with no description when the setting is on', function () {
    $settings = Make::settings(['keywordsExclude' => ['sales'], 'matchDescription' => true]);

    expect(KeywordFilter::passesExclude(Make::job(['description' => null]), $settings))->toBeTrue();
});

it('gives the first include keyword that matches, in the order of the settings', function (string $title, array $tags, array $include, ?string $first) {
    $job = Make::job(['title' => $title, 'tags' => $tags]);

    expect(KeywordFilter::firstInclude($job, Make::settings(['keywordsInclude' => $include])))->toBe($first);
})->with([
    'no include keywords' => [LARAVEL_TITLE, [], [], null],
    'no keyword matches' => [LARAVEL_TITLE, [], ['symfony', 'django'], null],
    'one keyword matches' => [LARAVEL_TITLE, [], ['symfony', 'Laravel'], 'Laravel'],
    'two keywords match, the first in the settings wins' => [LARAVEL_TITLE, [], ['PHP', 'Laravel'], 'PHP'],
    'two keywords match, other order' => [LARAVEL_TITLE, [], ['Laravel', 'PHP'], 'Laravel'],
    'keyword in a tag' => [BACKEND_TITLE, BACKEND_TAGS, ['rails', 'node.js'], 'node.js'],
    'same whole word rule as the filter' => [BACKEND_TITLE, BACKEND_TAGS, ['c', 'c#'], 'c#'],
]);

it('gives the first include keyword in the description only when the setting is on', function (bool $matchDescription, ?string $first) {
    $job = Make::job(['title' => BACKEND_TITLE, 'description' => OTE_TEXT]);

    expect(KeywordFilter::firstInclude($job, Make::settings(['keywordsInclude' => ['sales'], 'matchDescription' => $matchDescription])))->toBe($first);
})->with([
    'setting off' => [false, null],
    'setting on' => [true, 'sales'],
]);
