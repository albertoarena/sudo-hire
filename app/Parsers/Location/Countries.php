<?php

namespace App\Parsers\Location;

/**
 * Country names and common spellings to ISO 3166-1 alpha-2 codes.
 * The first name of each code is the short English name.
 * Pure: no I/O. See docs/DECISIONS.md "Country and region tables".
 */
final class Countries
{
    /** @var array<string, list<string>> */
    private const NAMES = [
        'AD' => ['Andorra'],
        'AE' => ['United Arab Emirates', 'UAE'],
        'AF' => ['Afghanistan'],
        'AG' => ['Antigua and Barbuda'],
        'AI' => ['Anguilla'],
        'AL' => ['Albania'],
        'AM' => ['Armenia'],
        'AO' => ['Angola'],
        'AQ' => ['Antarctica'],
        'AR' => ['Argentina'],
        'AS' => ['American Samoa'],
        'AT' => ['Austria'],
        'AU' => ['Australia'],
        'AW' => ['Aruba'],
        'AX' => ['Åland Islands', 'Aland Islands'],
        'AZ' => ['Azerbaijan'],
        'BA' => ['Bosnia and Herzegovina', 'Bosnia'],
        'BB' => ['Barbados'],
        'BD' => ['Bangladesh'],
        'BE' => ['Belgium'],
        'BF' => ['Burkina Faso'],
        'BG' => ['Bulgaria'],
        'BH' => ['Bahrain'],
        'BI' => ['Burundi'],
        'BJ' => ['Benin'],
        'BL' => ['Saint Barthélemy', 'Saint Barthelemy'],
        'BM' => ['Bermuda'],
        'BN' => ['Brunei', 'Brunei Darussalam'],
        'BO' => ['Bolivia', 'Bolivia (Plurinational State of)'],
        'BQ' => ['Bonaire, Sint Eustatius and Saba', 'Caribbean Netherlands'],
        'BR' => ['Brazil'],
        'BS' => ['Bahamas'],
        'BT' => ['Bhutan'],
        'BV' => ['Bouvet Island'],
        'BW' => ['Botswana'],
        'BY' => ['Belarus'],
        'BZ' => ['Belize'],
        'CA' => ['Canada'],
        'CC' => ['Cocos (Keeling) Islands', 'Cocos Islands'],
        'CD' => ['Democratic Republic of the Congo', 'Congo (Democratic Republic of the)', 'DR Congo', 'DRC'],
        'CF' => ['Central African Republic'],
        'CG' => ['Congo', 'Republic of the Congo'],
        'CH' => ['Switzerland'],
        'CI' => ['Côte d\'Ivoire', 'Cote d\'Ivoire', 'Ivory Coast'],
        'CK' => ['Cook Islands'],
        'CL' => ['Chile'],
        'CM' => ['Cameroon'],
        'CN' => ['China'],
        'CO' => ['Colombia'],
        'CR' => ['Costa Rica'],
        'CU' => ['Cuba'],
        'CV' => ['Cabo Verde', 'Cape Verde'],
        'CW' => ['Curaçao', 'Curacao'],
        'CX' => ['Christmas Island'],
        'CY' => ['Cyprus'],
        'CZ' => ['Czechia', 'Czech Republic'],
        'DE' => ['Germany'],
        'DJ' => ['Djibouti'],
        'DK' => ['Denmark'],
        'DM' => ['Dominica'],
        'DO' => ['Dominican Republic'],
        'DZ' => ['Algeria'],
        'EC' => ['Ecuador'],
        'EE' => ['Estonia'],
        'EG' => ['Egypt'],
        'EH' => ['Western Sahara'],
        'ER' => ['Eritrea'],
        'ES' => ['Spain'],
        'ET' => ['Ethiopia'],
        'FI' => ['Finland'],
        'FJ' => ['Fiji'],
        'FK' => ['Falkland Islands', 'Falkland Islands (Malvinas)'],
        'FM' => ['Micronesia', 'Micronesia (Federated States of)'],
        'FO' => ['Faroe Islands'],
        'FR' => ['France'],
        'GA' => ['Gabon'],
        'GB' => ['United Kingdom', 'United Kingdom of Great Britain and Northern Ireland', 'UK', 'Great Britain', 'Britain', 'England', 'Scotland', 'Wales', 'Northern Ireland'],
        'GD' => ['Grenada'],
        'GE' => ['Georgia'],
        'GF' => ['French Guiana'],
        'GG' => ['Guernsey'],
        'GH' => ['Ghana'],
        'GI' => ['Gibraltar'],
        'GL' => ['Greenland'],
        'GM' => ['Gambia'],
        'GN' => ['Guinea'],
        'GP' => ['Guadeloupe'],
        'GQ' => ['Equatorial Guinea'],
        'GR' => ['Greece'],
        'GS' => ['South Georgia and the South Sandwich Islands'],
        'GT' => ['Guatemala'],
        'GU' => ['Guam'],
        'GW' => ['Guinea-Bissau'],
        'GY' => ['Guyana'],
        'HK' => ['Hong Kong'],
        'HM' => ['Heard Island and McDonald Islands'],
        'HN' => ['Honduras'],
        'HR' => ['Croatia'],
        'HT' => ['Haiti'],
        'HU' => ['Hungary'],
        'ID' => ['Indonesia'],
        'IE' => ['Ireland'],
        'IL' => ['Israel'],
        'IM' => ['Isle of Man'],
        'IN' => ['India'],
        'IO' => ['British Indian Ocean Territory'],
        'IQ' => ['Iraq'],
        'IR' => ['Iran', 'Iran (Islamic Republic of)'],
        'IS' => ['Iceland'],
        'IT' => ['Italy'],
        'JE' => ['Jersey'],
        'JM' => ['Jamaica'],
        'JO' => ['Jordan'],
        'JP' => ['Japan'],
        'KE' => ['Kenya'],
        'KG' => ['Kyrgyzstan'],
        'KH' => ['Cambodia'],
        'KI' => ['Kiribati'],
        'KM' => ['Comoros'],
        'KN' => ['Saint Kitts and Nevis'],
        'KP' => ['North Korea', 'Korea (Democratic People\'s Republic of)'],
        'KR' => ['South Korea', 'Korea (Republic of)', 'Republic of Korea'],
        'KW' => ['Kuwait'],
        'KY' => ['Cayman Islands'],
        'KZ' => ['Kazakhstan'],
        'LA' => ['Laos', 'Lao People\'s Democratic Republic'],
        'LB' => ['Lebanon'],
        'LC' => ['Saint Lucia'],
        'LI' => ['Liechtenstein'],
        'LK' => ['Sri Lanka'],
        'LR' => ['Liberia'],
        'LS' => ['Lesotho'],
        'LT' => ['Lithuania'],
        'LU' => ['Luxembourg'],
        'LV' => ['Latvia'],
        'LY' => ['Libya'],
        'MA' => ['Morocco'],
        'MC' => ['Monaco'],
        'MD' => ['Moldova', 'Moldova (Republic of)', 'Republic of Moldova'],
        'ME' => ['Montenegro'],
        'MF' => ['Saint Martin', 'Saint Martin (French part)'],
        'MG' => ['Madagascar'],
        'MH' => ['Marshall Islands'],
        'MK' => ['North Macedonia', 'Macedonia'],
        'ML' => ['Mali'],
        'MM' => ['Myanmar', 'Burma'],
        'MN' => ['Mongolia'],
        'MO' => ['Macao', 'Macau'],
        'MP' => ['Northern Mariana Islands'],
        'MQ' => ['Martinique'],
        'MR' => ['Mauritania'],
        'MS' => ['Montserrat'],
        'MT' => ['Malta'],
        'MU' => ['Mauritius'],
        'MV' => ['Maldives'],
        'MW' => ['Malawi'],
        'MX' => ['Mexico'],
        'MY' => ['Malaysia'],
        'MZ' => ['Mozambique'],
        'NA' => ['Namibia'],
        'NC' => ['New Caledonia'],
        'NE' => ['Niger'],
        'NF' => ['Norfolk Island'],
        'NG' => ['Nigeria'],
        'NI' => ['Nicaragua'],
        'NL' => ['Netherlands', 'Holland'],
        'NO' => ['Norway'],
        'NP' => ['Nepal'],
        'NR' => ['Nauru'],
        'NU' => ['Niue'],
        'NZ' => ['New Zealand'],
        'OM' => ['Oman'],
        'PA' => ['Panama'],
        'PE' => ['Peru'],
        'PF' => ['French Polynesia'],
        'PG' => ['Papua New Guinea'],
        'PH' => ['Philippines'],
        'PK' => ['Pakistan'],
        'PL' => ['Poland'],
        'PM' => ['Saint Pierre and Miquelon'],
        'PN' => ['Pitcairn', 'Pitcairn Islands'],
        'PR' => ['Puerto Rico'],
        'PS' => ['Palestine', 'Palestine, State of', 'State of Palestine'],
        'PT' => ['Portugal'],
        'PW' => ['Palau'],
        'PY' => ['Paraguay'],
        'QA' => ['Qatar'],
        'RE' => ['Réunion', 'Reunion'],
        'RO' => ['Romania'],
        'RS' => ['Serbia'],
        'RU' => ['Russia', 'Russian Federation'],
        'RW' => ['Rwanda'],
        'SA' => ['Saudi Arabia'],
        'SB' => ['Solomon Islands'],
        'SC' => ['Seychelles'],
        'SD' => ['Sudan'],
        'SE' => ['Sweden'],
        'SG' => ['Singapore'],
        'SH' => ['Saint Helena', 'Saint Helena, Ascension and Tristan da Cunha'],
        'SI' => ['Slovenia'],
        'SJ' => ['Svalbard and Jan Mayen'],
        'SK' => ['Slovakia'],
        'SL' => ['Sierra Leone'],
        'SM' => ['San Marino'],
        'SN' => ['Senegal'],
        'SO' => ['Somalia'],
        'SR' => ['Suriname'],
        'SS' => ['South Sudan'],
        'ST' => ['Sao Tome and Principe', 'São Tomé and Príncipe'],
        'SV' => ['El Salvador'],
        'SX' => ['Sint Maarten', 'Sint Maarten (Dutch part)'],
        'SY' => ['Syria', 'Syrian Arab Republic'],
        'SZ' => ['Eswatini', 'Swaziland'],
        'TC' => ['Turks and Caicos Islands'],
        'TD' => ['Chad'],
        'TF' => ['French Southern Territories'],
        'TG' => ['Togo'],
        'TH' => ['Thailand'],
        'TJ' => ['Tajikistan'],
        'TK' => ['Tokelau'],
        'TL' => ['Timor-Leste', 'East Timor'],
        'TM' => ['Turkmenistan'],
        'TN' => ['Tunisia'],
        'TO' => ['Tonga'],
        'TR' => ['Türkiye', 'Turkiye', 'Turkey'],
        'TT' => ['Trinidad and Tobago'],
        'TV' => ['Tuvalu'],
        'TW' => ['Taiwan'],
        'TZ' => ['Tanzania', 'Tanzania, United Republic of', 'United Republic of Tanzania'],
        'UA' => ['Ukraine'],
        'UG' => ['Uganda'],
        'UM' => ['United States Minor Outlying Islands'],
        'US' => ['United States', 'United States of America', 'USA', 'US'],
        'UY' => ['Uruguay'],
        'UZ' => ['Uzbekistan'],
        'VA' => ['Holy See', 'Vatican City'],
        'VC' => ['Saint Vincent and the Grenadines'],
        'VE' => ['Venezuela', 'Venezuela (Bolivarian Republic of)'],
        'VG' => ['British Virgin Islands', 'Virgin Islands (British)'],
        'VI' => ['U.S. Virgin Islands', 'Virgin Islands (U.S.)'],
        'VN' => ['Vietnam', 'Viet Nam'],
        'VU' => ['Vanuatu'],
        'WF' => ['Wallis and Futuna'],
        'WS' => ['Samoa'],
        'YE' => ['Yemen'],
        'YT' => ['Mayotte'],
        'ZA' => ['South Africa'],
        'ZM' => ['Zambia'],
        'ZW' => ['Zimbabwe'],
    ];

    /** @var array<string, string>|null */
    private static ?array $lookup = null;

    /**
     * The ISO code for a country name, or null when the name is not in the table.
     */
    public static function code(string $name): ?string
    {
        if (self::$lookup === null) {
            self::$lookup = [];

            foreach (self::NAMES as $code => $names) {
                foreach ($names as $known) {
                    self::$lookup[self::normalise($known)] = $code;
                }
            }
        }

        return self::$lookup[self::normalise($name)] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::NAMES);
    }

    /**
     * @return list<string>
     */
    public static function names(string $code): array
    {
        return self::NAMES[$code] ?? [];
    }

    /**
     * Lowercase, no flag emoji, no dots, "&" as "and", no leading "the", single spaces.
     */
    public static function normalise(string $text): string
    {
        $text = mb_strtolower(Text::stripEmoji($text));
        $text = str_replace(['&', '.', '’'], [' and ', '', '\''], $text);
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return (string) preg_replace('/^the /', '', $text);
    }
}
