<?php

namespace App\Sources\Ats\Readers;

use App\Schema\InvalidJob;
use App\Schema\NormalisedJob;
use App\Sources\Ats\AtsItem;
use App\Sources\Ats\AtsSource;
use App\Sources\Ats\ReadFailed;
use App\Sources\SkipItem;
use App\Sources\UnreadableResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

/**
 * Shared HTTP and field helpers of the ATS readers.
 */
trait ReadsRawJobs
{
    /**
     * @throws ReadFailed
     */
    private function get(string $url): string
    {
        try {
            $response = Http::timeout(AtsSource::TIMEOUT_SECONDS)->get($url);
        } catch (ConnectionException $failed) {
            throw ReadFailed::because("Connection failed: {$failed->getMessage()}", $failed);
        }

        if ($response->failed()) {
            throw ReadFailed::because("HTTP {$response->status()}", $response->toException());
        }

        return $response->body();
    }

    /**
     * @return array<mixed>
     *
     * @throws ReadFailed
     */
    private function getJson(string $url): array
    {
        try {
            $data = json_decode($this->get($url), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $invalid) {
            throw ReadFailed::because('The response is not valid JSON.', $invalid);
        }

        if (! is_array($data)) {
            throw self::unreadable('The response is not a JSON object or list.');
        }

        return $data;
    }

    /**
     * @return list<array<mixed>>
     *
     * @throws ReadFailed
     */
    private function jobList(mixed $data): array
    {
        if (! is_array($data) || ! array_is_list($data)) {
            throw self::unreadable('The response has no job list.');
        }

        return array_values(array_filter($data, 'is_array'));
    }

    /**
     * The response came, but it cannot be read: the error kind is "parse".
     */
    private static function unreadable(string $reason): ReadFailed
    {
        return ReadFailed::because($reason, new UnreadableResponse($reason));
    }

    /**
     * A field as a trimmed string. Empty gives null.
     */
    private function text(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * @throws SkipItem
     */
    private function required(mixed $value, string $field): string
    {
        return $this->text($value) ?? throw SkipItem::because("The job has no {$field}.");
    }

    /**
     * @throws SkipItem
     */
    private function date(mixed $value, string $field): CarbonImmutable
    {
        $text = $this->required($value, $field);

        try {
            return CarbonImmutable::parse($text)->utc();
        } catch (Throwable) {
            throw SkipItem::because("The job has a bad {$field}: \"{$text}\".");
        }
    }

    /**
     * @throws SkipItem
     */
    private function optionalDate(mixed $value, string $field): ?CarbonImmutable
    {
        return $this->text($value) === null ? null : $this->date($value, $field);
    }

    /**
     * @param  array<mixed>  $values
     * @return list<string>
     */
    private function texts(array $values): array
    {
        return array_values(array_filter(array_map(fn (mixed $value) => $this->text($value), $values)));
    }

    /**
     * The common fields of an ATS job, and the job built from them.
     *
     * @param  array<string, mixed>  $fields  NormalisedJob arguments
     *
     * @throws SkipItem
     */
    private function job(AtsItem $item, string $id, string $company, array $fields): NormalisedJob
    {
        try {
            return new NormalisedJob(...array_merge($fields, [
                'source' => AtsSource::KEY,
                'sourceJobId' => "{$item->company->key()}:{$id}",
                'company' => $company,
                'fetchedAt' => $item->fetchedAt,
                'attribution' => "via {$company} careers",
            ]));
        } catch (InvalidJob $invalid) {
            throw SkipItem::because($invalid->getMessage());
        }
    }
}
