<?php

namespace App\Parsers\Title;

use App\Schema\ContractType;
use App\Schema\Seniority;

/**
 * What the title parser reads from one job title.
 */
final readonly class ParsedTitle
{
    public function __construct(
        public Seniority $seniority,
        public ContractType $contractType,
    ) {}
}
