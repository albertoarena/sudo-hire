<?php

use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case
|--------------------------------------------------------------------------
|
| Feature tests boot Laravel. Unit tests are plain PHP: parsers, value
| objects and other pure classes must not need the framework.
|
*/

pest()->extend(TestCase::class)->in('Feature');

/**
 * A valid set of NormalisedJob arguments. Tests override what they need.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function jobAttributes(array $overrides = []): array
{
    return array_merge([
        'source' => 'fake',
        'sourceJobId' => '42',
        'sourceUrl' => 'https://example.com/jobs/42',
        'applyUrl' => 'https://example.com/jobs/42/apply',
        'title' => 'Senior Laravel Developer',
        'company' => 'Acme',
        'description' => null,
        'tags' => [],
        'workMode' => WorkMode::Remote,
        'eligibility' => Eligibility::unknown(),
        'officeLocation' => null,
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => null,
        'contractType' => ContractType::FullTime,
        'seniority' => Seniority::Senior,
        'postedAt' => CarbonImmutable::parse('2026-10-27T09:00:00Z'),
        'fetchedAt' => CarbonImmutable::parse('2026-10-28T09:00:00Z'),
        'expiresAt' => null,
        'attribution' => 'via Fake',
    ], $overrides);
}
