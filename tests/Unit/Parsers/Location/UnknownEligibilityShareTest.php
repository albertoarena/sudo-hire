<?php

use App\Parsers\Location\UnknownEligibilityShare;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;

function jobWithEligibility(Eligibility $eligibility): NormalisedJob
{
    return new NormalisedJob(...jobAttributes(['eligibility' => $eligibility]));
}

it('gives zero for no jobs', function () {
    $share = UnknownEligibilityShare::of([]);

    expect($share->unknown)->toBe(0)
        ->and($share->total)->toBe(0)
        ->and($share->share)->toBe(0.0);
});

it('gives one when all jobs are unknown', function () {
    $share = UnknownEligibilityShare::of([
        jobWithEligibility(Eligibility::unknown()),
        jobWithEligibility(Eligibility::unknown()),
    ]);

    expect($share->unknown)->toBe(2)
        ->and($share->total)->toBe(2)
        ->and($share->share)->toBe(1.0);
});

it('counts only unknown eligibility in a mix', function () {
    $share = UnknownEligibilityShare::of([
        jobWithEligibility(Eligibility::unknown()),
        jobWithEligibility(Eligibility::worldwide()),
        jobWithEligibility(Eligibility::restricted(countries: ['IT'])),
        jobWithEligibility(Eligibility::unknown()),
    ]);

    expect($share->unknown)->toBe(2)
        ->and($share->total)->toBe(4)
        ->and($share->share)->toBe(0.5);
});
