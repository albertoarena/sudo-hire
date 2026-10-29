<?php

namespace Tests\Support;

use App\Schema\NormalisedJob;

/**
 * A NormalisedJob as a plain array, so a test can compare it field by field.
 * The description is left out: tests check it on its own.
 */
final class JobFields
{
    /**
     * @return array<string, mixed>
     */
    public static function of(NormalisedJob $job): array
    {
        return [
            'source' => $job->source,
            'sourceJobId' => $job->sourceJobId,
            'sourceUrl' => $job->sourceUrl,
            'applyUrl' => $job->applyUrl,
            'title' => $job->title,
            'company' => $job->company,
            'tags' => $job->tags,
            'workMode' => $job->workMode->value,
            'eligibility' => [
                'type' => $job->eligibility->type->value,
                'regions' => $job->eligibility->regions,
                'countries' => $job->eligibility->countries,
            ],
            'officeLocation' => $job->officeLocation,
            'timezoneMin' => $job->timezoneMin,
            'timezoneMax' => $job->timezoneMax,
            'salary' => $job->salary === null ? null : [
                'min' => $job->salary->min,
                'max' => $job->salary->max,
                'currency' => $job->salary->currency,
                'period' => $job->salary->period->value,
                'raw' => $job->salary->raw,
            ],
            'contractType' => $job->contractType->value,
            'seniority' => $job->seniority->value,
            'postedAt' => $job->postedAt->toIso8601ZuluString(),
            'fetchedAt' => $job->fetchedAt->toIso8601ZuluString(),
            'expiresAt' => $job->expiresAt?->toIso8601ZuluString(),
            'attribution' => $job->attribution,
        ];
    }

    public static function fixture(string $path): string
    {
        return (string) file_get_contents(__DIR__.'/../Fixtures/'.$path);
    }

    /**
     * @return array<mixed>
     */
    public static function json(string $path): array
    {
        return (array) json_decode(self::fixture($path), true, flags: JSON_THROW_ON_ERROR);
    }
}
