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

// "{when}" becomes "at 21:00", or "at the next scan" when the time is not known.
const DETAILS: Record<string, [string, string]> = {
    certificate: ['Certificate error', 'The secure connection failed. Sudo Hire tries again {when}. If it continues, update the app.'],
    http: ['No answer', 'The site did not answer. Sudo Hire tries again {when}.'],
    parse: ['Read error', 'The answer of the site could not be read. Sudo Hire tries again {when}.'],
    other: ['Read error', 'Sudo Hire could not read this source. It tries again {when}.'],
};

/** The error name and one plain sentence, for the Sources pane. `retryAt` is the next try, from nextTry(). */
export function errorDetails(kind: string, retryAt: string | null = null): [string, string] {
    const [name, why] = DETAILS[kind] ?? DETAILS.other;

    return [name, why.replace('{when}', retryAt === null ? 'at the next scan' : `at ${clock(retryAt)}`)];
}

/**
 * When a failed source tries again: its last run plus its minimum interval, also after a
 * failed run. Null when that time has passed or the source never ran: then the next scan tries.
 */
export function nextTry(source: Source, now: Date = new Date()): string | null {
    if (source.lastRunAt === null) {
        return null;
    }

    const at = new Date(Date.parse(source.lastRunAt) + source.minIntervalMinutes * 60_000);

    return at > now ? at.toISOString() : null;
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
