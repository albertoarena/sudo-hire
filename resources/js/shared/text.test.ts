import { describe, expect, it } from 'vitest';
import { source } from '../settings/fixtures';
import { sourceWarning, stampLines, tilt } from './text';

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
