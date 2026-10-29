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
use App\Sources\Support\HtmlToText;

/**
 * Greenhouse job board API. See the field mapping in docs/DECISIONS.md.
 */
final class GreenhouseReader implements AtsReader
{
    use ReadsRawJobs;

    public function ats(): Ats
    {
        return Ats::Greenhouse;
    }

    public function read(CompanyEntry $company): array
    {
        $data = $this->getJson('https://boards-api.greenhouse.io/v1/boards/'.rawurlencode($company->slug).'/jobs?content=true');

        return $this->jobList($data['jobs'] ?? null);
    }

    public function normalise(AtsItem $item): NormalisedJob
    {
        $raw = $item->data;
        $url = $this->required($raw['absolute_url'] ?? null, 'link');
        $content = $this->text($raw['content'] ?? null);
        $postedAt = $this->date($raw['first_published'] ?? null, 'publish date');
        $departments = is_array($raw['departments'] ?? null) ? $raw['departments'] : [];

        return $this->job($item, $this->required($raw['id'] ?? null, 'id'), $this->text($raw['company_name'] ?? null) ?? $item->company->displayName, [
            'sourceUrl' => $url,
            'applyUrl' => $url,
            'title' => $this->required($raw['title'] ?? null, 'title'),
            // The content is HTML with escaped entities: decode once, then strip the tags.
            'description' => HtmlToText::convert($content === null ? null : html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'tags' => $this->texts(array_map(fn (mixed $department) => is_array($department) ? ($department['name'] ?? null) : null, $departments)),
            // The location is free text: the eligibility parser (M4) reads it.
            'workMode' => WorkMode::Unknown,
            'eligibility' => Eligibility::unknown(),
            'officeLocation' => null,
            'timezoneMin' => null,
            'timezoneMax' => null,
            'salary' => null,
            'contractType' => ContractType::Unknown,
            'seniority' => Seniority::Unknown,
            'postedAt' => $postedAt,
            'expiresAt' => $this->optionalDate($raw['application_deadline'] ?? null, 'application deadline'),
        ]);
    }
}
