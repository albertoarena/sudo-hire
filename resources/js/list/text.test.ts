import { describe, expect, it } from 'vitest';
import { row } from './fixtures';
import { age, terms } from './text';

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
