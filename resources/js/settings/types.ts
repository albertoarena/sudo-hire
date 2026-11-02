// The JSON shapes of the settings API, in the shape of Settings::toArray() (docs/DECISIONS.md, M8a and M8c entries).

export interface Settings {
    country: string | null;
    timezone: string;
    workModes: string[];
    unknownEligibility: 'group' | 'strict' | 'lenient';
    timezoneOverlapHours: number | null;
    keywordsInclude: string[];
    keywordsExclude: string[];
    matchDescription: boolean;
    minAnnualSalary: number | null;
    minDayRate: number | null;
    unknownSalary: 'strict' | 'lenient';
    contractTypes: string[];
    seniorities: string[];
    postedWithinHours: number;
    companyBlocklist: string[];
    disabledSources: string[];
    displayCurrency: string;
    monthlyPayments: number;
    hoursPerDay: number;
    notificationIntervalHours: number;
}

export interface Company {
    id: number;
    provider: string;
    slug: string;
    name: string | null;
    enabled: boolean;
    lastCheckedAt: string | null;
    lastJobCount: number | null;
    lastError: { message: string; kind: string } | null;
}

export interface HiddenJobs {
    jobs: { id: number; title: string; company: string; hiddenAt: string | null }[];
    olderCount: number;
}

export interface Rates {
    date: string | null;
    source: string;
}

export type Pane = 'where' | 'jobs' | 'pay' | 'notifications' | 'sources' | 'companies' | 'privacy' | 'troubleshooting';
