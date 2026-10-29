<?php

namespace App\Schema;

enum EligibilityType: string
{
    case Worldwide = 'worldwide';
    case Restricted = 'restricted';
    case Unknown = 'unknown';
}
