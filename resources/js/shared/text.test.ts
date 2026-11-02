import { describe, expect, it } from 'vitest';
import { source } from '../settings/fixtures';
import { clock, errorDetails, nextTry, sourceWarning, stampLines, tilt } from './text';

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

describe('sourceWarning', () => {
    it('names the first enabled source with an error', () => {
        expect(sourceWarning([])).toBeNull();
        expect(
            sourceWarning([
                source({ key: 'jobicy', name: 'Jobicy', enabled: false, lastError: { message: 'x', kind: 'http' } }),
                source({ key: 'weworkremotely', name: 'We Work Remotely', lastError: { message: 'cURL error 60', kind: 'certificate' } }),
            ]),
        ).toBe('We Work Remotely: certificate error');
    });
});

describe('nextTry', () => {
    const failed = source({ key: 'weworkremotely', minIntervalMinutes: 120, lastRunAt: '2026-11-02T18:00:03Z', lastError: { message: 'x', kind: 'http' } });

    it('is the last run plus the minimum interval', () => {
        expect(nextTry(failed, new Date('2026-11-02T19:10:00Z'))).toBe('2026-11-02T20:00:03.000Z');
    });

    it('is null when that time has passed or the source never ran', () => {
        expect(nextTry(failed, new Date('2026-11-02T20:00:04Z'))).toBeNull();
        expect(nextTry(source({ lastRunAt: null }), new Date('2026-11-02T19:10:00Z'))).toBeNull();
    });
});

describe('errorDetails', () => {
    it('gives the time of the next try', () => {
        const [name, why] = errorDetails('http', '2026-11-02T20:00:03Z');

        expect(name).toBe('No answer');
        expect(why).toBe(`The site did not answer. Sudo Hire tries again at ${clock('2026-11-02T20:00:03Z')}.`);
    });

    it('says "at the next scan" with no time', () => {
        expect(errorDetails('parse')[1]).toBe('The answer of the site could not be read. Sudo Hire tries again at the next scan.');
        expect(errorDetails('certificate', '2026-11-02T20:00:03Z')[1]).toBe(
            `The secure connection failed. Sudo Hire tries again at ${clock('2026-11-02T20:00:03Z')}. If it continues, update the app.`,
        );
        expect(errorDetails('other')[1]).toBe('Sudo Hire could not read this source. It tries again at the next scan.');
    });
});
