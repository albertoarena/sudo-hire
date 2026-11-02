import type { Source, Stamp } from './types';

/** The stamp tilt from the job id: -2.0 to +2.0 degrees, fixed per job (DESIGN.md). */
export function tilt(id: number): string {
    return `${(((id * 37) % 41) - 20) / 10}deg`;
}

/** The two stamp lines: the place, then the verdict. */
export function stampLines(stamp: Stamp): [string, string] {
    if (stamp.kind === 'not_stated') {
        return ['Location', 'Not stated'];
    }

    if (stamp.kind === 'refused') {
        return ['Not', 'Admitted'];
    }

    return [stamp.place ?? 'Anywhere', 'Admitted'];
}

/** "16:05", in the time zone of the Mac. */
export function clock(iso: string): string {
    return new Date(iso).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

const ERRORS: Record<string, string> = {
    certificate: 'certificate error',
    http: 'the site did not answer',
    parse: 'the answer could not be read',
    other: 'read error',
};

/** The short error text of a source, e.g. "certificate error". */
export function errorText(kind: string): string {
    return ERRORS[kind] ?? ERRORS.other;
}

const DETAILS: Record<string, [string, string]> = {
    certificate: ['Certificate error', 'The secure connection failed. Sudo Hire tries again at the next scan. If it continues, update the app.'],
    http: ['No answer', 'The site did not answer. Sudo Hire tries again at the next scan.'],
    parse: ['Read error', 'The answer of the site could not be read. Sudo Hire tries again at the next scan.'],
    other: ['Read error', 'Sudo Hire could not read this source. It tries again at the next scan.'],
};

/** The error name and one plain sentence, for the Sources pane. */
export function errorDetails(kind: string): [string, string] {
    return DETAILS[kind] ?? DETAILS.other;
}

/** The enabled sources whose last read failed. */
export function failedSources(sources: Source[]): Source[] {
    return sources.filter((source) => source.enabled && source.lastError !== null);
}

/** The footer warning: the first enabled source with an error, e.g. "We Work Remotely: certificate error". */
export function sourceWarning(sources: Source[]): string | null {
    const failed = failedSources(sources)[0];

    return failed ? `${failed.name}: ${errorText(failed.lastError!.kind)}` : null;
}
