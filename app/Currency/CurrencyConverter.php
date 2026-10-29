<?php

namespace App\Currency;

/**
 * Converts amounts between currencies through EUR. Pure: no I/O.
 */
final readonly class CurrencyConverter
{
    /**
     * @param  array<string, float>  $rates  units of each currency for 1 EUR, e.g. ['USD' => 1.1133]
     */
    public function __construct(private array $rates) {}

    /**
     * Null when a currency has no rate.
     */
    public function convert(float $amount, string $from, string $to): ?float
    {
        $fromRate = $this->rate($from);
        $toRate = $this->rate($to);

        if ($fromRate === null || $toRate === null) {
            return null;
        }

        return $from === $to ? $amount : $amount / $fromRate * $toRate;
    }

    private function rate(string $currency): ?float
    {
        return $currency === 'EUR' ? 1.0 : ($this->rates[$currency] ?? null);
    }
}
