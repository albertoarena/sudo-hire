<?php

namespace App\Currency;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Throwable;

/**
 * Reads the ECB daily reference rates XML. See docs/DECISIONS.md for the terms.
 */
final class EcbRatesClient
{
    public const string URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    /**
     * @throws RatesFetchFailed
     */
    public function fetch(): EcbRates
    {
        try {
            $body = Http::timeout(30)->get(self::URL)->throw()->body();
        } catch (ConnectionException|RequestException $e) {
            throw new RatesFetchFailed('ECB rates fetch failed: '.$e->getMessage(), 0, $e);
        }

        return $this->parse($body);
    }

    private function parse(string $body): EcbRates
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($body);
        } catch (Throwable $e) {
            throw new RatesFetchFailed('ECB rates are not XML.', 0, $e);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xml->registerXPathNamespace('ecb', 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref');
        $day = ($xml->xpath('//ecb:Cube[@time]') ?: [])[0] ?? null;
        $rates = [];

        foreach ($xml->xpath('//ecb:Cube[@time][1]/ecb:Cube[@currency][@rate]') ?: [] as $cube) {
            $rate = (float) $cube['rate'];

            if (preg_match('/^[A-Z]{3}$/', (string) $cube['currency']) === 1 && $rate > 0) {
                $rates[(string) $cube['currency']] = $rate;
            }
        }

        if ($day === null || $rates === []) {
            throw new RatesFetchFailed('ECB XML has no rates.');
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', (string) $day['time'], 'UTC');
        } catch (Throwable $e) {
            throw new RatesFetchFailed('ECB XML has no valid date.', 0, $e);
        }

        if (! $date instanceof CarbonImmutable) {
            throw new RatesFetchFailed('ECB XML has no valid date.');
        }

        return new EcbRates($date, $rates);
    }
}
