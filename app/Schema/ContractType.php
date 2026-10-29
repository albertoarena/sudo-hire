<?php

namespace App\Schema;

enum ContractType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Unknown = 'unknown';
}
