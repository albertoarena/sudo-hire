<?php

use App\Scan\SummaryText;
use Tests\Unit\Filters\Make;

/**
 * @param  list<array{0: string, 1?: list<string>}>  $jobs  title and tags
 */
function summaryOf(array $jobs, array $include): string
{
    return SummaryText::of(
        array_map(fn (array $job) => Make::job(['title' => $job[0], 'tags' => $job[1] ?? []]), $jobs),
        Make::settings(['keywordsInclude' => $include]),
    );
}

it('groups the jobs by the first include keyword, in the order of the settings', function () {
    $jobs = [
        ['Senior Laravel Developer'],
        ['PHP Engineer'],
        ['Laravel Engineer'],
        ['Backend Engineer', ['php']],
        ['Full-Stack Laravel Developer'],
        ['Laravel Team Lead'],
    ];

    expect(summaryOf($jobs, ['Laravel', 'PHP']))->toBe('6 new jobs: 4 Laravel, 2 PHP');
});

it('counts a job that matches two keywords once, under the first keyword in the settings', function () {
    $jobs = [['Senior PHP/Laravel Developer'], ['PHP Engineer']];

    expect(summaryOf($jobs, ['Laravel', 'PHP']))->toBe('2 new jobs: 1 Laravel, 1 PHP')
        ->and(summaryOf($jobs, ['PHP', 'Laravel']))->toBe('2 new jobs: 2 PHP');
});

it('leaves out a keyword that no job matches', function () {
    expect(summaryOf([['PHP Engineer'], ['PHP Developer']], ['Laravel', 'Symfony', 'PHP']))->toBe('2 new jobs: 2 PHP');
});

it('gives only the count with no include keywords', function () {
    expect(summaryOf([['Senior Laravel Developer'], ['PHP Engineer']], []))->toBe('2 new jobs');
});

it('uses the singular for one job', function (array $include, string $text) {
    expect(summaryOf([['Senior Laravel Developer']], $include))->toBe($text);
})->with([
    'with include keywords' => [['Laravel'], '1 new job: 1 Laravel'],
    'no include keywords' => [[], '1 new job'],
]);

it('starts each keyword with a capital letter and keeps the rest as the user wrote it', function () {
    $jobs = [['Senior Laravel Developer'], ['PHP Engineer'], ['Node.js Developer']];

    expect(summaryOf($jobs, ['laravel', 'PHP', 'node.js']))->toBe('3 new jobs: 1 Laravel, 1 PHP, 1 Node.js')
        ->and(summaryOf([['Développeur Élixir']], ['élixir']))->toBe('1 new job: 1 Élixir');
});
