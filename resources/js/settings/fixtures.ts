import type { Company, HiddenJobs, Settings } from './types';
import type { Source } from '../shared/types';

// Test data in the shape of the API. Titles and companies are invented.
export function settings(overrides: Partial<Settings> = {}): Settings {
    return {
        country: 'IT',
        timezone: 'Europe/Rome',
        workModes: ['remote'],
        unknownEligibility: 'group',
        timezoneOverlapHours: null,
        keywordsInclude: ['laravel', 'php'],
        keywordsExclude: [],
        matchDescription: false,
        minAnnualSalary: null,
        minDayRate: null,
        unknownSalary: 'lenient',
        contractTypes: ['full_time', 'part_time', 'contract'],
        seniorities: ['junior', 'mid', 'senior', 'lead'],
        postedWithinHours: 72,
        companyBlocklist: [],
        disabledSources: [],
        displayCurrency: 'EUR',
        monthlyPayments: 12,
        hoursPerDay: 8,
        notificationIntervalHours: 2,
        ...overrides,
    };
}

export function source(overrides: Partial<Source> = {}): Source {
    return {
        key: 'larajobs',
        name: 'LaraJobs',
        enabled: true,
        minIntervalMinutes: 120,
        lastRunAt: '2026-11-02T15:05:00Z',
        lastSuccessAt: '2026-11-02T15:05:02Z',
        jobCount: 5,
        lastError: null,
        ...overrides,
    };
}

export function company(overrides: Partial<Company> = {}): Company {
    return {
        id: 1,
        provider: 'greenhouse',
        slug: 'acme',
        name: 'Acme',
        enabled: true,
        lastCheckedAt: '2026-11-02T15:05:00Z',
        lastJobCount: 12,
        lastError: null,
        ...overrides,
    };
}

export function hiddenJobs(overrides: Partial<HiddenJobs> = {}): HiddenJobs {
    return {
        jobs: [{ id: 31, title: 'PHP Developer', company: 'Globex', hiddenAt: '2026-11-01T08:00:00Z' }],
        olderCount: 0,
        ...overrides,
    };
}

export const countries = [
    { code: 'DE', name: 'Germany' },
    { code: 'IT', name: 'Italy' },
];
