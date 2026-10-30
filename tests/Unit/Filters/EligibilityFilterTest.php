<?php

use App\Filters\EligibilityFilter;
use App\Filters\EligibilityOutcome;
use App\Schema\Eligibility;
use Tests\Unit\Filters\Make;

it('checks the job eligibility against the user country', function (Eligibility $eligibility, ?string $country, string $mode, EligibilityOutcome $expected) {
    $job = Make::job(['eligibility' => $eligibility]);
    $settings = Make::settings(['country' => $country, 'unknownEligibility' => $mode]);

    expect(EligibilityFilter::outcome($job, $settings))->toBe($expected);
})->with([
    'worldwide' => [Eligibility::worldwide(), 'IT', 'strict', EligibilityOutcome::Pass],
    'country match' => [Eligibility::restricted([], ['DE', 'IT']), 'IT', 'strict', EligibilityOutcome::Pass],
    'region match, IT and europe' => [Eligibility::restricted(['europe']), 'IT', 'strict', EligibilityOutcome::Pass],
    'region match, IT and eu' => [Eligibility::restricted(['eu']), 'IT', 'strict', EligibilityOutcome::Pass],
    'region match, IT and emea' => [Eligibility::restricted(['emea']), 'IT', 'strict', EligibilityOutcome::Pass],
    'no match, US only' => [Eligibility::restricted([], ['US']), 'IT', 'lenient', EligibilityOutcome::Fail],
    'no match, north america' => [Eligibility::restricted(['north_america']), 'IT', 'lenient', EligibilityOutcome::Fail],
    'no match, GB is not in eu' => [Eligibility::restricted(['eu']), 'GB', 'lenient', EligibilityOutcome::Fail],
    'unknown, group' => [Eligibility::unknown(), 'IT', 'group', EligibilityOutcome::Group],
    'unknown, strict' => [Eligibility::unknown(), 'IT', 'strict', EligibilityOutcome::Fail],
    'unknown, lenient' => [Eligibility::unknown(), 'IT', 'lenient', EligibilityOutcome::Pass],
    'country null, restricted' => [Eligibility::restricted([], ['US']), null, 'strict', EligibilityOutcome::Pass],
    'country null, unknown and group' => [Eligibility::unknown(), null, 'group', EligibilityOutcome::Pass],
]);
