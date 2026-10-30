<?php

namespace Tests\Unit\Filters;

use App\Schema\NormalisedJob;
use App\Settings\Settings;

/**
 * Builds jobs and settings for the filter tests.
 */
final class Make
{
    /**
     * @param  array<string, mixed>  $overrides  NormalisedJob arguments, see jobAttributes()
     */
    public static function job(array $overrides = []): NormalisedJob
    {
        return new NormalisedJob(...jobAttributes($overrides));
    }

    /**
     * The default settings for Europe/Rome, with some values changed.
     *
     * @param  array<string, mixed>  $overrides  values in the shape of Settings::toArray()
     */
    public static function settings(array $overrides = []): Settings
    {
        return Settings::fromArray(array_merge(Settings::defaults('Europe/Rome')->toArray(), $overrides));
    }

    /**
     * True when the text is in the fixture file, as it is or JSON escaped.
     */
    public static function inFixture(string $file, string $text): bool
    {
        $raw = (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/'.$file);

        return str_contains($raw, $text)
            || str_contains($raw, substr((string) json_encode($text), 1, -1))
            || str_contains($raw, substr((string) json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1));
    }
}
