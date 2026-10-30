<?php

namespace App\Currency;

/**
 * The currencies the app can show values in (REQUIREMENTS section 7).
 */
enum DisplayCurrency: string
{
    case Eur = 'EUR';
    case Usd = 'USD';
    case Gbp = 'GBP';
}
