// The JSON shapes of the M8a API (docs/DECISIONS.md, M8a entries).

export type JobState = 'saved' | 'applied' | 'hidden';

export interface Stamp {
    kind: 'admitted' | 'not_stated' | 'refused';
    place: string | null;
}

export interface Row {
    id: number;
    title: string;
    company: string;
    source: string;
    attribution: string;
    alsoOn: string[];
    postedAt: string;
    salary: { original: string | null; converted: string | null; unknown: boolean; ote: boolean };
    terms: { contractType: string; seniority: string };
    stamp: Stamp;
    sourceUrl: string;
    applyUrl: string;
    seen: boolean;
    state: JobState | null;
}

export interface Counts {
    new: number;
    saved: number;
    applied: number;
    locationNotStated: number;
    unseen: number;
}

export interface ListView {
    counts: Counts;
    new: Row[];
    locationNotStated: Row[];
    saved: Row[];
    applied: Row[];
}

export interface Status {
    lastScan: { startedAt: string | null; finishedAt: string | null };
    nextScanAt: string | null;
}

export interface Source {
    key: string;
    name: string;
    enabled: boolean;
    lastError: { message: string; kind: string } | null;
}

export type Tab = 'new' | 'saved' | 'applied';
