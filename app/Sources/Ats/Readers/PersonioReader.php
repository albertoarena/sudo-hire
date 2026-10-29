<?php

namespace App\Sources\Ats\Readers;

use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use App\Sources\Ats\Ats;
use App\Sources\Ats\AtsItem;
use App\Sources\Ats\AtsReader;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\ReadFailed;
use App\Sources\SkipItem;
use App\Sources\Support\HtmlToText;
use SimpleXMLElement;

/**
 * Personio XML feed. See the field mapping and the Personio entries in
 * docs/DECISIONS.md.
 */
final class PersonioReader implements AtsReader
{
    use ReadsRawJobs;

    private const FIELDS = ['id', 'name', 'office', 'department', 'keywords', 'employmentType', 'schedule', 'seniority', 'createdAt'];

    public function ats(): Ats
    {
        return Ats::Personio;
    }

    public function read(CompanyEntry $company): array
    {
        $body = $this->get("https://{$company->slug}.jobs.personio.de/xml");

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, options: LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false || $xml->getName() !== 'workzag-jobs') {
            throw ReadFailed::because('The response is not Personio XML.');
        }

        $items = [];

        foreach ($xml->position as $position) {
            $items[] = $this->toArray($position);
        }

        return $items;
    }

    public function normalise(AtsItem $item): NormalisedJob
    {
        $raw = $item->data;
        $id = $this->required($raw['id'] ?? null, 'id');

        if (preg_match('/^\d+$/', $id) !== 1) {
            throw SkipItem::because("The job id \"{$id}\" is not a number.");
        }

        // No link in the feed: the company's own job page is built from the slug and the id.
        $url = "https://{$item->company->slug}.jobs.personio.de/job/{$id}";
        $offices = $this->texts([$raw['office'] ?? null, ...(is_array($raw['additionalOffices'] ?? null) ? $raw['additionalOffices'] : [])]);

        return $this->job($item, $id, $item->company->displayName, [
            'sourceUrl' => $url,
            'applyUrl' => $url,
            'title' => $this->required($raw['name'] ?? null, 'title'),
            'description' => $this->description($raw['jobDescriptions'] ?? null),
            'tags' => [...explode(',', $this->text($raw['keywords'] ?? null) ?? ''), $this->text($raw['department'] ?? null) ?? ''],
            // No remote field: the title and description parser (M4) gives the work mode.
            'workMode' => WorkMode::Unknown,
            'eligibility' => Eligibility::unknown(),
            'officeLocation' => $offices === [] ? null : implode('; ', array_values(array_unique($offices))),
            'timezoneMin' => null,
            'timezoneMax' => null,
            'salary' => null,
            'contractType' => match ([$this->text($raw['employmentType'] ?? null), $this->text($raw['schedule'] ?? null)]) {
                ['permanent', 'full-time'] => ContractType::FullTime,
                ['permanent', 'part-time'] => ContractType::PartTime,
                default => in_array($this->text($raw['employmentType'] ?? null), ['freelance', 'temporary'], true)
                    ? ContractType::Contract
                    : ContractType::Unknown,
            },
            'seniority' => match ($this->text($raw['seniority'] ?? null)) {
                'entry-level', 'student' => Seniority::Junior,
                'senior' => Seniority::Senior,
                'lead' => Seniority::Lead,
                default => Seniority::Unknown,
            },
            'postedAt' => $this->date($raw['createdAt'] ?? null, 'created date'),
            'expiresAt' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(SimpleXMLElement $position): array
    {
        $item = [];

        foreach (self::FIELDS as $field) {
            $item[$field] = isset($position->{$field}) ? trim((string) $position->{$field}) : null;
        }

        $item['additionalOffices'] = [];

        foreach ($position->additionalOffices->office ?? [] as $office) {
            $item['additionalOffices'][] = trim((string) $office);
        }

        $item['jobDescriptions'] = [];

        foreach ($position->jobDescriptions->jobDescription ?? [] as $description) {
            $item['jobDescriptions'][] = ['name' => trim((string) $description->name), 'value' => (string) $description->value];
        }

        return $item;
    }

    /**
     * "name: value" pairs. The part named "URL" is a page slug, not text.
     */
    private function description(mixed $descriptions): ?string
    {
        $parts = [];

        foreach (is_array($descriptions) ? $descriptions : [] as $description) {
            $name = is_array($description) ? $this->text($description['name'] ?? null) : null;
            $value = is_array($description) ? HtmlToText::convert($this->text($description['value'] ?? null)) : null;

            if ($name === 'URL' || $value === null) {
                continue;
            }

            $parts[] = $name === null ? $value : "{$name}: {$value}";
        }

        return $parts === [] ? null : implode("\n\n", $parts);
    }
}
