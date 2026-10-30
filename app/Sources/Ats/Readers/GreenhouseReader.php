<?php

namespace App\Sources\Ats\Readers;

use App\Parsers\Location\LocationParser;
use App\Parsers\Salary\SalaryParser;
use App\Schema\ContractType;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\Seniority;
use App\Sources\Ats\Ats;
use App\Sources\Ats\AtsItem;
use App\Sources\Ats\AtsReader;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Support\HtmlToText;
use App\Sources\Support\TitleFallback;

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
        // The content is HTML with escaped entities: decode once to get the HTML.
        $html = $content === null ? null : html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = $this->required($raw['title'] ?? null, 'title');
        $location = (new LocationParser)->parse($this->text(is_array($raw['location'] ?? null) ? ($raw['location']['name'] ?? null) : null) ?? '');
        $postedAt = $this->date($raw['first_published'] ?? null, 'publish date');
        $departments = is_array($raw['departments'] ?? null) ? $raw['departments'] : [];

        return $this->job($item, $this->required($raw['id'] ?? null, 'id'), $this->text($raw['company_name'] ?? null) ?? $item->company->displayName, [
            'sourceUrl' => $url,
            'applyUrl' => $url,
            'title' => $title,
            'description' => HtmlToText::convert($html),
            'tags' => $this->texts(array_map(fn (mixed $department) => is_array($department) ? ($department['name'] ?? null) : null, $departments)),
            'workMode' => $location->workMode,
            'eligibility' => $location->eligibility,
            'officeLocation' => $location->officeLocation,
            'timezoneMin' => $location->timezoneMin,
            'timezoneMax' => $location->timezoneMax,
            'salary' => $this->payRange($html),
            'contractType' => TitleFallback::contractType(ContractType::Unknown, $title),
            'seniority' => TitleFallback::seniority(Seniority::Unknown, $title),
            'postedAt' => $postedAt,
            'expiresAt' => $this->optionalDate($raw['application_deadline'] ?? null, 'application deadline'),
        ]);
    }

    /**
     * Some US and Canada jobs have a pay range: <div class="pay-range">, with one span per part.
     */
    private function payRange(?string $html): ?Salary
    {
        if ($html === null || preg_match('#<div class="pay-range">(.*?)</div>#s', $html, $match) !== 1) {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' ? null : (new SalaryParser)->parse($text);
    }
}
