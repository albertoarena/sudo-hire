<?php

use App\Parsers\Salary\Ote;
use Tests\Unit\Filters\Make;

it('reads OTE from real strings of the fixtures', function (string $file, string $text, bool $expected) {
    expect(Make::inFixture($file, $text))->toBeTrue("\"{$text}\" is not in {$file}");

    expect(Ote::in($text))->toBe($expected);
})->with([
    'Remotive salary field' => ['remotive/remote-jobs-contract.json', 'OTE $25k - $35k', true],
    'Remotive description' => ['remotive/remote-jobs-contract.json', 'Average first year OTE: 25K-35K (US) Annually', true],
    'We Work Remotely, words and abbreviation' => ['weworkremotely/back-end.rss', 'On Target Earnings (OTE)', true],
    'We Work Remotely, words only' => ['weworkremotely/back-end.rss', 'offer a competitive On Target Earnings', true],
    'salary with no OTE' => ['remotive/remote-jobs.json', '$170k - $200k', false],
    'hourly rate' => ['remotive/remote-jobs-contract.json', '$50-$75 /hour', false],
    '"ote" inside a word' => ['greenhouse/jobs.json', 'Remote, Canada; Remote, United States', false],
]);

it('reads "on-target earnings" with a hyphen (not from a fixture)', function () {
    expect(Ote::in('$90k on-target earnings'))->toBeTrue();
});
