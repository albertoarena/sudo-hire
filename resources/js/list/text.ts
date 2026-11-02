import type { Row, Source, Stamp } from './types';

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

const CONTRACT: Record<string, string> = { full_time: 'Full time', part_time: 'Part time', contract: 'Contract' };
const SENIORITY: Record<string, string> = { junior: 'Junior', mid: 'Mid', senior: 'Senior', lead: 'Lead' };

/** "Full time, Senior". Unknown values are left out. */
export function terms(row: Row): string {
    return [CONTRACT[row.terms.contractType], SENIORITY[row.terms.seniority]].filter(Boolean).join(', ');
}

/** "2 days ago", from the posted time. */
export function age(postedAt: string, now: Date = new Date()): string {
    const minutes = Math.max(0, Math.floor((now.getTime() - new Date(postedAt).getTime()) / 60000));
    const plural = (n: number, unit: string) => `${n} ${unit}${n === 1 ? '' : 's'} ago`;

    if (minutes < 60) {
        return minutes < 2 ? 'Just now' : plural(minutes, 'minute');
    }

    const hours = Math.floor(minutes / 60);

    return hours < 24 ? plural(hours, 'hour') : plural(Math.floor(hours / 24), 'day');
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

/** The footer warning: the first enabled source with an error, e.g. "We Work Remotely: certificate error". */
export function sourceWarning(sources: Source[]): string | null {
    const failed = sources.find((source) => source.enabled && source.lastError !== null);

    return failed ? `${failed.name}: ${ERRORS[failed.lastError!.kind] ?? ERRORS.other}` : null;
}
