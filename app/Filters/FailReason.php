<?php

namespace App\Filters;

/**
 * Why a job fails: one case for each filter of REQUIREMENTS section 8.
 */
enum FailReason: string
{
    case WorkMode = 'work_mode';
    case Eligibility = 'eligibility';
    case Timezone = 'timezone';
    case KeywordMissing = 'keyword_missing';
    case KeywordExcluded = 'keyword_excluded';
    case Salary = 'salary';
    case ContractType = 'contract_type';
    case Seniority = 'seniority';
    case PostedWithin = 'posted_within';
    case Company = 'company';
    case Source = 'source';
    case Expired = 'expired';
}
