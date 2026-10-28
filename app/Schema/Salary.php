<?php

namespace App\Schema;

/**
 * A salary or rate as the source states it. See docs/REQUIREMENTS.md section 7.
 */
final readonly class Salary
{
    /**
     * @param  string  $currency  ISO 4217 code, e.g. "EUR"
     * @param  string  $raw  the original text
     */
    public function __construct(
        public ?float $min,
        public ?float $max,
        public string $currency,
        public SalaryPeriod $period,
        public string $raw,
    ) {
        if ($min === null && $max === null) {
            throw new InvalidJob('Salary needs a min or a max.');
        }

        if (($min !== null && $min < 0) || ($max !== null && $max < 0)) {
            throw new InvalidJob('Salary cannot be negative.');
        }

        if ($min !== null && $max !== null && $min > $max) {
            throw new InvalidJob('Salary min cannot be above max.');
        }

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new InvalidJob("Currency must be ISO 4217, got \"{$currency}\".");
        }
    }

    /**
     * @param  array{min?: int|float|null, max?: int|float|null, currency: string, period: string, raw: string}  $data
     */
    public static function fromArray(array $data): self
    {
        $period = SalaryPeriod::tryFrom($data['period'])
            ?? throw new InvalidJob("Unknown salary period \"{$data['period']}\".");

        return new self(
            isset($data['min']) ? (float) $data['min'] : null,
            isset($data['max']) ? (float) $data['max'] : null,
            $data['currency'],
            $period,
            $data['raw'],
        );
    }
}
