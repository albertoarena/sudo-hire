<?php

namespace App\Currency;

use App\Models\ExchangeRate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The stored ECB rates. Fetches at most once a day and keeps the last rates when a fetch fails.
 */
final class ExchangeRates
{
    public function __construct(private readonly EcbRatesClient $client) {}

    /**
     * Fetches new rates unless a fetch already succeeded on the same UTC day.
     *
     * @return bool true when new rates were stored
     */
    public function refresh(?CarbonImmutable $now = null): bool
    {
        $now = ($now ?? CarbonImmutable::now())->utc();
        $lastFetch = ExchangeRate::query()->max('fetched_at');

        if (is_string($lastFetch) && CarbonImmutable::parse($lastFetch, 'UTC')->isSameDay($now)) {
            return false;
        }

        try {
            $rates = $this->client->fetch();
        } catch (RatesFetchFailed $e) {
            Log::warning($e->getMessage());

            return false;
        }

        DB::transaction(function () use ($rates, $now) {
            ExchangeRate::query()->delete();

            foreach ($rates->rates as $currency => $rate) {
                ExchangeRate::create([
                    'currency' => $currency,
                    'rate' => $rate,
                    'rate_date' => $rates->date,
                    'fetched_at' => $now,
                ]);
            }
        });

        return true;
    }

    /**
     * The date of the stored rates, for the settings screen. Null before the first fetch.
     */
    public function rateDate(): ?CarbonImmutable
    {
        return ExchangeRate::query()->first()?->rate_date;
    }

    public function converter(): CurrencyConverter
    {
        /** @var array<string, float> $rates */
        $rates = ExchangeRate::query()->pluck('rate', 'currency')->all();

        return new CurrencyConverter($rates);
    }
}
