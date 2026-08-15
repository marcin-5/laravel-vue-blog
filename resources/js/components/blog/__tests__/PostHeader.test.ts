import type { PostDetails } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PostHeader from '../PostHeader.vue';

vi.mock('@/components/blog/ViewStats.vue', () => ({
    default: { name: 'ViewStats', props: ['anonymous', 'bots', 'consented', 'markdown'], template: '<div class="view-stats" />' },
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

const post: PostDetails = {
    id: 1,
    title: 'Post title',
    slug: 'post-title',
    author: 'Author',
    author_email: 'author@test',
    contentHtml: '<p>Body</p>',
    published_at: '2024-01-01T10:00:00Z',
};

const mountHeader = (seo?: { publishedTime?: string | null; modifiedTime?: string | null } | null) =>
    mount(PostHeader, { props: { post, locale: 'pl', seo } });

describe('PostHeader.vue', () => {
    it('shows the updated date when the post was modified more than a day after publication', () => {
        const wrapper = mountHeader({ publishedTime: '2024-01-01T10:00:00Z', modifiedTime: '2024-01-05T10:00:00Z' });

        expect(wrapper.text()).toContain('blog.post.updated');
    });

    it('hides the updated date for a negligible modification and when seo is missing', () => {
        const sameDay = mountHeader({ publishedTime: '2024-01-01T10:00:00Z', modifiedTime: '2024-01-01T12:00:00Z' });
        expect(sameDay.text()).not.toContain('blog.post.updated');

        expect(mountHeader().text()).not.toContain('blog.post.updated');
    });

    it('renders the view stats only when they are available', () => {
        expect(mountHeader().findComponent({ name: 'ViewStats' }).exists()).toBe(false);

        const wrapper = mount(PostHeader, {
            props: { post, viewStats: { anonymous: 1, bots: 2, consented: 3, markdown: 4 } },
        });

        expect(wrapper.findComponent({ name: 'ViewStats' }).props('anonymous')).toBe(1);
    });
});
