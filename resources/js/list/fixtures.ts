import type { ListView, Row, Source, Status } from './types';

// Test data in the shape of the M8a API. Titles and companies are invented.
export function row(overrides: Partial<Row> = {}): Row {
    return {
        id: 7,
        title: 'Senior Laravel Developer',
        company: 'Acme',
        source: 'himalayas',
        attribution: 'via Himalayas',
        alsoOn: [],
        postedAt: new Date(Date.now() - 2 * 86400000).toISOString(),
        salary: { original: null, converted: null, unknown: true, ote: false },
        terms: { contractType: 'full_time', seniority: 'senior' },
        stamp: { kind: 'admitted', place: 'IT' },
        sourceUrl: 'https://example.com/jobs/7',
        applyUrl: 'https://example.com/jobs/7/apply',
        seen: false,
        state: null,
        ...overrides,
    };
}

export function view(overrides: Partial<ListView> = {}): ListView {
    const lists = { new: [row()], locationNotStated: [], saved: [], applied: [], ...overrides };

    return {
        ...lists,
        counts: {
            new: lists.new.length,
            saved: lists.saved.length,
            applied: lists.applied.length,
            locationNotStated: lists.locationNotStated.length,
            unseen: lists.new.filter((item) => !item.seen).length,
        },
    };
}

export const status: Status = {
    lastScan: { startedAt: '2026-11-02T15:05:00Z', finishedAt: '2026-11-02T15:05:03Z' },
    nextScanAt: '2026-11-02T15:10:00Z',
};

export const sources: Source[] = [
    { key: 'himalayas', name: 'Himalayas', enabled: true, lastError: null },
    { key: 'weworkremotely', name: 'We Work Remotely', enabled: true, lastError: null },
];
