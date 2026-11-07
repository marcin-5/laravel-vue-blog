import { ref } from 'vue';
import { describe, expect, it } from 'vitest';
import { useBlogExcerpts } from '../useBlogExcerpts';

describe('useBlogExcerpts', () => {
    it('initializes with default value true', () => {
        const { showExcerpts } = useBlogExcerpts('blog1');
        expect(showExcerpts.value).toBe(true);
    });

    it('shares state for same blogSlug', () => {
        const { showExcerpts: excerpts1 } = useBlogExcerpts('blog4');
        const { showExcerpts: excerpts2 } = useBlogExcerpts('blog4');

        excerpts1.value = false;
        expect(excerpts2.value).toBe(false);
    });

    it('follows the blog slug when it is passed as a getter', () => {
        const slug = ref('blogReactiveA');
        const { showExcerpts } = useBlogExcerpts(() => slug.value);
        useBlogExcerpts('blogReactiveB').showExcerpts.value = false;

        expect(showExcerpts.value).toBe(true);

        slug.value = 'blogReactiveB';

        expect(showExcerpts.value).toBe(false);
    });

    it('maintains separate state for different slugs', () => {
        const { showExcerpts: excerptsA } = useBlogExcerpts('blogA');
        const { showExcerpts: excerptsB } = useBlogExcerpts('blogB');

        excerptsA.value = false;
        excerptsB.value = true;

        expect(excerptsA.value).toBe(false);
        expect(excerptsB.value).toBe(true);
    });
});
