import { describe, expect, it } from 'vitest';
import { hasContent } from '../stringUtils';

describe('stringUtils', () => {
    describe('hasContent', () => {
        it('returns false for null', () => {
            expect(hasContent(null)).toBe(false);
        });

        it('returns false for undefined', () => {
            expect(hasContent(undefined)).toBe(false);
        });

        it('returns false for an empty string', () => {
            expect(hasContent('')).toBe(false);
        });

        it('returns false for a whitespace only string', () => {
            expect(hasContent('   \n\t ')).toBe(false);
        });

        it('returns true for a string with content', () => {
            expect(hasContent('x')).toBe(true);
        });

        it('returns true for a padded string with content', () => {
            expect(hasContent('  hello  ')).toBe(true);
        });
    });
});
