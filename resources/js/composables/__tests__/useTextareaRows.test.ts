import { useTextareaRows } from '@/composables/useTextareaRows';
import { effectScope } from 'vue';
import { beforeEach, describe, expect, it } from 'vitest';

describe('useTextareaRows', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('defaults to five rows and persists changes independently by key', async () => {
        const scope = effectScope();
        const state = scope.run(() => useTextareaRows('blog.description'))!;

        expect(state.rows.value).toBe(5);
        state.increase();
        await Promise.resolve();

        expect(state.rows.value).toBe(6);
        expect(localStorage.getItem('markdown-textarea-rows:blog.description')).toBe('6');
        expect(localStorage.getItem('markdown-textarea-rows:blog.about')).toBeNull();
        scope.stop();
    });

    it('restores valid values, clamps out-of-range values and falls back for malformed values', () => {
        localStorage.setItem('markdown-textarea-rows:post.content', '12');
        localStorage.setItem('markdown-textarea-rows:post.summary', '99');
        localStorage.setItem('markdown-textarea-rows:blog.about', 'invalid');

        const scope = effectScope();
        const restored = scope.run(() => useTextareaRows('post.content'))!;
        const clamped = scope.run(() => useTextareaRows('post.summary'))!;
        const fallback = scope.run(() => useTextareaRows('blog.about'))!;

        expect(restored.rows.value).toBe(12);
        expect(clamped.rows.value).toBe(50);
        expect(fallback.rows.value).toBe(5);
        scope.stop();
    });

    it('does not exceed the row limits', () => {
        localStorage.setItem('markdown-textarea-rows:min', '3');
        localStorage.setItem('markdown-textarea-rows:max', '50');

        const scope = effectScope();
        const minimum = scope.run(() => useTextareaRows('min'))!;
        const maximum = scope.run(() => useTextareaRows('max'))!;

        minimum.decrease();
        maximum.increase();

        expect(minimum.rows.value).toBe(3);
        expect(minimum.canDecrease.value).toBe(false);
        expect(maximum.rows.value).toBe(50);
        expect(maximum.canIncrease.value).toBe(false);
        scope.stop();
    });
});