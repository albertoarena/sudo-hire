// The JSON shapes of the API that both windows read (docs/DECISIONS.md, M8a and M8c entries).

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
    minIntervalMinutes: number;
    lastRunAt: string | null;
    lastSuccessAt: string | null;
    jobCount: number;
    lastError: { message: string; kind: string } | null;
}

export interface Country {
    code: string;
    name: string;
}

/** A job of the stamp preview (GET /api/stamp-preview). */
export interface PreviewJob {
    id: number;
    title: string;
    company: string;
    stamp: Stamp;
}

export type Tab = 'new' | 'saved' | 'applied';
