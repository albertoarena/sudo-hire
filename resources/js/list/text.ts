import type { Row } from '../shared/types';

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
