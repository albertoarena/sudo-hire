<?php

namespace App\Parsers\Location;

/**
 * Each country to its regions, and region names in text to region keys.
 * Sub-regions come from the UN M49 standard. EU members come from europa.eu.
 * Pure: no I/O. See docs/DECISIONS.md "Country and region tables".
 */
final class Regions
{
    /** @var list<string> */
    private const KEYS = ['africa', 'americas', 'apac', 'asia', 'emea', 'eu', 'europe', 'latam', 'middle_east', 'north_america', 'oceania'];

    /** @var array<string, list<string>> M49 sub-region to region keys */
    private const SUB_REGIONS = [
        'northern_africa' => ['africa', 'emea'],
        'eastern_africa' => ['africa', 'emea'],
        'middle_africa' => ['africa', 'emea'],
        'southern_africa' => ['africa', 'emea'],
        'western_africa' => ['africa', 'emea'],
        'caribbean' => ['americas', 'latam'],
        'central_america' => ['americas', 'latam'],
        'south_america' => ['americas', 'latam'],
        'northern_america' => ['americas', 'north_america'],
        'central_asia' => ['asia'],
        'eastern_asia' => ['asia', 'apac'],
        'south_eastern_asia' => ['asia', 'apac'],
        'southern_asia' => ['asia', 'apac'],
        'western_asia' => ['asia', 'emea'],
        'eastern_europe' => ['europe', 'emea'],
        'northern_europe' => ['europe', 'emea'],
        'southern_europe' => ['europe', 'emea'],
        'western_europe' => ['europe', 'emea'],
        'australia_nz' => ['oceania', 'apac'],
        'melanesia' => ['oceania', 'apac'],
        'micronesia' => ['oceania', 'apac'],
        'polynesia' => ['oceania', 'apac'],
        'antarctica' => [],
    ];

    /** @var array<string, string> country code to M49 sub-region */
    private const COUNTRIES = [
        'AD' => 'southern_europe', 'AE' => 'western_asia', 'AF' => 'southern_asia', 'AG' => 'caribbean',
        'AI' => 'caribbean', 'AL' => 'southern_europe', 'AM' => 'western_asia', 'AO' => 'middle_africa',
        'AQ' => 'antarctica', 'AR' => 'south_america', 'AS' => 'polynesia', 'AT' => 'western_europe',
        'AU' => 'australia_nz', 'AW' => 'caribbean', 'AX' => 'northern_europe', 'AZ' => 'western_asia',
        'BA' => 'southern_europe', 'BB' => 'caribbean', 'BD' => 'southern_asia', 'BE' => 'western_europe',
        'BF' => 'western_africa', 'BG' => 'eastern_europe', 'BH' => 'western_asia', 'BI' => 'eastern_africa',
        'BJ' => 'western_africa', 'BL' => 'caribbean', 'BM' => 'northern_america', 'BN' => 'south_eastern_asia',
        'BO' => 'south_america', 'BQ' => 'caribbean', 'BR' => 'south_america', 'BS' => 'caribbean',
        'BT' => 'southern_asia', 'BV' => 'south_america', 'BW' => 'southern_africa', 'BY' => 'eastern_europe',
        'BZ' => 'central_america', 'CA' => 'northern_america', 'CC' => 'australia_nz', 'CD' => 'middle_africa',
        'CF' => 'middle_africa', 'CG' => 'middle_africa', 'CH' => 'western_europe', 'CI' => 'western_africa',
        'CK' => 'polynesia', 'CL' => 'south_america', 'CM' => 'middle_africa', 'CN' => 'eastern_asia',
        'CO' => 'south_america', 'CR' => 'central_america', 'CU' => 'caribbean', 'CV' => 'western_africa',
        'CW' => 'caribbean', 'CX' => 'australia_nz', 'CY' => 'western_asia', 'CZ' => 'eastern_europe',
        'DE' => 'western_europe', 'DJ' => 'eastern_africa', 'DK' => 'northern_europe', 'DM' => 'caribbean',
        'DO' => 'caribbean', 'DZ' => 'northern_africa', 'EC' => 'south_america', 'EE' => 'northern_europe',
        'EG' => 'northern_africa', 'EH' => 'northern_africa', 'ER' => 'eastern_africa', 'ES' => 'southern_europe',
        'ET' => 'eastern_africa', 'FI' => 'northern_europe', 'FJ' => 'melanesia', 'FK' => 'south_america',
        'FM' => 'micronesia', 'FO' => 'northern_europe', 'FR' => 'western_europe', 'GA' => 'middle_africa',
        'GB' => 'northern_europe', 'GD' => 'caribbean', 'GE' => 'western_asia', 'GF' => 'south_america',
        'GG' => 'northern_europe', 'GH' => 'western_africa', 'GI' => 'southern_europe', 'GL' => 'northern_america',
        'GM' => 'western_africa', 'GN' => 'western_africa', 'GP' => 'caribbean', 'GQ' => 'middle_africa',
        'GR' => 'southern_europe', 'GS' => 'south_america', 'GT' => 'central_america', 'GU' => 'micronesia',
        'GW' => 'western_africa', 'GY' => 'south_america', 'HK' => 'eastern_asia', 'HM' => 'australia_nz',
        'HN' => 'central_america', 'HR' => 'southern_europe', 'HT' => 'caribbean', 'HU' => 'eastern_europe',
        'ID' => 'south_eastern_asia', 'IE' => 'northern_europe', 'IL' => 'western_asia', 'IM' => 'northern_europe',
        'IN' => 'southern_asia', 'IO' => 'eastern_africa', 'IQ' => 'western_asia', 'IR' => 'southern_asia',
        'IS' => 'northern_europe', 'IT' => 'southern_europe', 'JE' => 'northern_europe', 'JM' => 'caribbean',
        'JO' => 'western_asia', 'JP' => 'eastern_asia', 'KE' => 'eastern_africa', 'KG' => 'central_asia',
        'KH' => 'south_eastern_asia', 'KI' => 'micronesia', 'KM' => 'eastern_africa', 'KN' => 'caribbean',
        'KP' => 'eastern_asia', 'KR' => 'eastern_asia', 'KW' => 'western_asia', 'KY' => 'caribbean',
        'KZ' => 'central_asia', 'LA' => 'south_eastern_asia', 'LB' => 'western_asia', 'LC' => 'caribbean',
        'LI' => 'western_europe', 'LK' => 'southern_asia', 'LR' => 'western_africa', 'LS' => 'southern_africa',
        'LT' => 'northern_europe', 'LU' => 'western_europe', 'LV' => 'northern_europe', 'LY' => 'northern_africa',
        'MA' => 'northern_africa', 'MC' => 'western_europe', 'MD' => 'eastern_europe', 'ME' => 'southern_europe',
        'MF' => 'caribbean', 'MG' => 'eastern_africa', 'MH' => 'micronesia', 'MK' => 'southern_europe',
        'ML' => 'western_africa', 'MM' => 'south_eastern_asia', 'MN' => 'eastern_asia', 'MO' => 'eastern_asia',
        'MP' => 'micronesia', 'MQ' => 'caribbean', 'MR' => 'western_africa', 'MS' => 'caribbean',
        'MT' => 'southern_europe', 'MU' => 'eastern_africa', 'MV' => 'southern_asia', 'MW' => 'eastern_africa',
        'MX' => 'central_america', 'MY' => 'south_eastern_asia', 'MZ' => 'eastern_africa', 'NA' => 'southern_africa',
        'NC' => 'melanesia', 'NE' => 'western_africa', 'NF' => 'australia_nz', 'NG' => 'western_africa',
        'NI' => 'central_america', 'NL' => 'western_europe', 'NO' => 'northern_europe', 'NP' => 'southern_asia',
        'NR' => 'micronesia', 'NU' => 'polynesia', 'NZ' => 'australia_nz', 'OM' => 'western_asia',
        'PA' => 'central_america', 'PE' => 'south_america', 'PF' => 'polynesia', 'PG' => 'melanesia',
        'PH' => 'south_eastern_asia', 'PK' => 'southern_asia', 'PL' => 'eastern_europe', 'PM' => 'northern_america',
        'PN' => 'polynesia', 'PR' => 'caribbean', 'PS' => 'western_asia', 'PT' => 'southern_europe',
        'PW' => 'micronesia', 'PY' => 'south_america', 'QA' => 'western_asia', 'RE' => 'eastern_africa',
        'RO' => 'eastern_europe', 'RS' => 'southern_europe', 'RU' => 'eastern_europe', 'RW' => 'eastern_africa',
        'SA' => 'western_asia', 'SB' => 'melanesia', 'SC' => 'eastern_africa', 'SD' => 'northern_africa',
        'SE' => 'northern_europe', 'SG' => 'south_eastern_asia', 'SH' => 'western_africa', 'SI' => 'southern_europe',
        'SJ' => 'northern_europe', 'SK' => 'eastern_europe', 'SL' => 'western_africa', 'SM' => 'southern_europe',
        'SN' => 'western_africa', 'SO' => 'eastern_africa', 'SR' => 'south_america', 'SS' => 'eastern_africa',
        'ST' => 'middle_africa', 'SV' => 'central_america', 'SX' => 'caribbean', 'SY' => 'western_asia',
        'SZ' => 'southern_africa', 'TC' => 'caribbean', 'TD' => 'middle_africa', 'TF' => 'eastern_africa',
        'TG' => 'western_africa', 'TH' => 'south_eastern_asia', 'TJ' => 'central_asia', 'TK' => 'polynesia',
        'TL' => 'south_eastern_asia', 'TM' => 'central_asia', 'TN' => 'northern_africa', 'TO' => 'polynesia',
        'TR' => 'western_asia', 'TT' => 'caribbean', 'TV' => 'polynesia', 'TW' => 'eastern_asia',
        'TZ' => 'eastern_africa', 'UA' => 'eastern_europe', 'UG' => 'eastern_africa', 'UM' => 'micronesia',
        'US' => 'northern_america', 'UY' => 'south_america', 'UZ' => 'central_asia', 'VA' => 'southern_europe',
        'VC' => 'caribbean', 'VE' => 'south_america', 'VG' => 'caribbean', 'VI' => 'caribbean',
        'VN' => 'south_eastern_asia', 'VU' => 'melanesia', 'WF' => 'polynesia', 'WS' => 'polynesia',
        'YE' => 'western_asia', 'YT' => 'eastern_africa', 'ZA' => 'southern_africa', 'ZM' => 'eastern_africa',
        'ZW' => 'eastern_africa',
    ];

    /** @var list<string> the 27 EU member states */
    private const EU = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU',
        'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
    ];

    /** @var list<string> the Middle East as job posts use it */
    private const MIDDLE_EAST = ['AE', 'BH', 'EG', 'IL', 'IQ', 'IR', 'JO', 'KW', 'LB', 'OM', 'PS', 'QA', 'SA', 'SY', 'TR', 'YE'];

    /** @var array<string, string> region names in text (normalised) to region keys */
    private const NAMES = [
        'africa' => 'africa',
        'americas' => 'americas',
        'apac' => 'apac',
        'asia pacific' => 'apac',
        'asia-pacific' => 'apac',
        'asia' => 'asia',
        'emea' => 'emea',
        'eu' => 'eu',
        'european union' => 'eu',
        'europe' => 'europe',
        'latam' => 'latam',
        'latin america' => 'latam',
        'middle east' => 'middle_east',
        'north america' => 'north_america',
        'oceania' => 'oceania',
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return self::KEYS;
    }

    /**
     * @return list<string>
     */
    public static function countries(): array
    {
        return array_keys(self::COUNTRIES);
    }

    /**
     * The regions of a country code, e.g. "IT" gives eu, europe and emea.
     *
     * @return list<string>
     */
    public static function of(string $code): array
    {
        if (! isset(self::COUNTRIES[$code])) {
            return [];
        }

        $regions = self::SUB_REGIONS[self::COUNTRIES[$code]];

        if (in_array($code, self::EU, true)) {
            array_push($regions, 'eu', 'europe');
        }

        if (in_array($code, self::MIDDLE_EAST, true)) {
            array_push($regions, 'middle_east', 'emea');
            $regions = array_diff($regions, ['apac']);
        }

        return array_values(array_unique($regions));
    }

    /**
     * The region key for a region name, e.g. "Latin America" gives "latam". Null when the text is not a region.
     */
    public static function key(string $name): ?string
    {
        return self::NAMES[Countries::normalise($name)] ?? null;
    }
}
