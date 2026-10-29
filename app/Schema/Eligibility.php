<?php

namespace App\Schema;

/**
 * Where a job can be done from. See docs/REQUIREMENTS.md section 6.
 */
final readonly class Eligibility
{
    /** @var list<string> */
    public array $regions;

    /** @var list<string> */
    public array $countries;

    /**
     * @param  list<string>  $regions  lowercase keys, e.g. "eu", "north_america"
     * @param  list<string>  $countries  ISO 3166-1 alpha-2 codes, e.g. "IT"
     */
    private function __construct(public EligibilityType $type, array $regions, array $countries)
    {
        foreach ($regions as $region) {
            if (preg_match('/^[a-z]+(_[a-z]+)*$/', $region) !== 1) {
                throw new InvalidJob("Region must be a lowercase key, got \"{$region}\".");
            }
        }

        foreach ($countries as $country) {
            if (preg_match('/^[A-Z]{2}$/', $country) !== 1) {
                throw new InvalidJob("Country must be ISO 3166-1 alpha-2, got \"{$country}\".");
            }
        }

        $hasPlaces = $regions !== [] || $countries !== [];

        if ($type === EligibilityType::Restricted && ! $hasPlaces) {
            throw new InvalidJob('Restricted eligibility needs at least one region or country.');
        }

        if ($type !== EligibilityType::Restricted && $hasPlaces) {
            throw new InvalidJob("Eligibility \"{$type->value}\" cannot have regions or countries.");
        }

        $this->regions = array_values(array_unique($regions));
        $this->countries = array_values(array_unique($countries));
    }

    public static function worldwide(): self
    {
        return new self(EligibilityType::Worldwide, [], []);
    }

    public static function unknown(): self
    {
        return new self(EligibilityType::Unknown, [], []);
    }

    /**
     * @param  list<string>  $regions
     * @param  list<string>  $countries
     */
    public static function restricted(array $regions = [], array $countries = []): self
    {
        return new self(EligibilityType::Restricted, $regions, $countries);
    }

    /**
     * @param  array{type: string, regions?: list<string>, countries?: list<string>}  $data
     */
    public static function fromArray(array $data): self
    {
        $type = EligibilityType::tryFrom($data['type'])
            ?? throw new InvalidJob("Unknown eligibility type \"{$data['type']}\".");

        return new self($type, $data['regions'] ?? [], $data['countries'] ?? []);
    }
}
