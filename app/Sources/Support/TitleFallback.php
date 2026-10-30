<?php

namespace App\Sources\Support;

use App\Parsers\Title\TitleParser;
use App\Schema\ContractType;
use App\Schema\Seniority;

/**
 * A structured source field always wins. Only an unknown value is read from the title.
 */
final class TitleFallback
{
    public static function seniority(Seniority $structured, string $title): Seniority
    {
        return $structured === Seniority::Unknown ? (new TitleParser)->parse($title)->seniority : $structured;
    }

    public static function contractType(ContractType $structured, string $title): ContractType
    {
        return $structured === ContractType::Unknown ? (new TitleParser)->parse($title)->contractType : $structured;
    }
}
