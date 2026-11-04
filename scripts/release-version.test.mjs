import { describe, expect, it } from 'vitest';
import { versionFromTag } from './release-version.mjs';

describe('versionFromTag', () => {
    it('removes the v of the tag', () => {
        expect(versionFromTag('v1.0.0')).toBe('1.0.0');
    });

    it('keeps a pre-release part', () => {
        expect(versionFromTag('v1.0.0-rc.1')).toBe('1.0.0-rc.1');
    });

    it('accepts the full ref of the tag', () => {
        expect(versionFromTag('refs/tags/v2.3.4')).toBe('2.3.4');
    });

    it.each(['1.0.0', 'v1.0', 'v1.0.0.0', 'main', 'v1.0.0-', ''])('refuses %j', (tag) => {
        expect(() => versionFromTag(tag)).toThrow(`not a release tag: ${tag}`);
    });
});
