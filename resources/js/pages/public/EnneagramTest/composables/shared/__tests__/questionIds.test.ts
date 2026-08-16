import { describe, expect, it } from 'vitest';
import { isStage1Part1Question, isStage1Part2Question, isStage1Question, isStage2Question } from '../questionIds';

describe('questionIds', () => {
    it('detects stage 1 parts from question IDs', () => {
        expect(isStage1Part1Question({ id: 'di-01' })).toBe(true);
        expect(isStage1Part2Question({ id: 'ri-01' })).toBe(true);
        expect(isStage1Question({ id: 'di-01' })).toBe(true);
        expect(isStage1Question({ id: 'ri-01' })).toBe(true);
    });

    it('detects stage 2 instinct sets from question IDs', () => {
        expect(isStage2Question({ id: 'sp-01' })).toBe(true);
        expect(isStage2Question({ id: 'so-01' })).toBe(true);
        expect(isStage2Question({ id: 'sx-01' })).toBe(true);
        expect(isStage2Question({ id: 'ri-01' })).toBe(false);
    });
});
