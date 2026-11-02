import { describe, expect, it } from 'vitest';
import { row } from './fixtures';
import { age, sourceWarning, stampLines, terms, tilt } from './text';

describe('tilt', () => {
    it('gives a fixed angle from the job id, from -2 to 2 degrees', () => {
        expect(tilt(1)).toBe('1.7deg');
        expect(tilt(648)).toBe('1.2deg');
        expect(tilt(648)).toBe(tilt(648));

        for (let id = 1; id < 500; id++) {
            expect(Math.abs(parseFloat(tilt(id)))).toBeLessThanOrEqual(2);
        }
    });
});

describe('stampLines', () => {
    it('gives the place and the verdict', () => {
        expect(stampLines({ kind: 'admitted', place: 'IT' })).toEqual(['IT', 'Admitted']);
        expect(stampLines({ kind: 'admitted', place: 'Europe' })).toEqual(['Europe', 'Admitted']);
        expect(stampLines({ kind: 'not_stated', place: 'Location' })).toEqual(['Location', 'Not stated']);
        expect(stampLines({ kind: 'refused', place: null })).toEqual(['Not', 'Admitted']);
    });
});

describe('terms', () => {
    it('names the contract type and the seniority, and leaves out unknown values', () => {
        expect(terms(row())).toBe('Full time, Senior');
        expect(terms(row({ terms: { contractType: 'contract', seniority: 'unknown' } }))).toBe('Contract');
        expect(terms(row({ terms: { contractType: 'unknown', seniority: 'unknown' } }))).toBe('');
    });
});

describe('age', () => {
    const now = new Date('2026-11-02T12:00:00Z');

    it('says how long ago the job was posted', () => {
        expect(age('2026-11-02T11:59:30Z', now)).toBe('Just now');
        expect(age('2026-11-02T11:15:00Z', now)).toBe('45 minutes ago');
        expect(age('2026-11-02T11:00:00Z', now)).toBe('1 hour ago');
        expect(age('2026-11-01T12:00:00Z', now)).toBe('1 day ago');
        expect(age('2026-10-30T12:00:00Z', now)).toBe('3 days ago');
    });
});

describe('sourceWarning', () => {
    it('names the first enabled source with an error', () => {
        expect(sourceWarning([])).toBeNull();
        expect(
            sourceWarning([
                { key: 'jobicy', name: 'Jobicy', enabled: false, lastError: { message: 'x', kind: 'http' } },
                { key: 'weworkremotely', name: 'We Work Remotely', enabled: true, lastError: { message: 'cURL error 60', kind: 'certificate' } },
            ]),
        ).toBe('We Work Remotely: certificate error');
    });
});
