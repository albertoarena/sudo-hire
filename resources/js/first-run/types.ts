// The shapes of the first-run API (GET and POST /api/first-scan).
export type ScanStatus = 'waiting' | 'reading' | 'done' | 'failed';

export interface FirstScan {
    sources: { key: string; name: string; status: ScanStatus; errorKind: string | null }[];
    finished: boolean;
}
