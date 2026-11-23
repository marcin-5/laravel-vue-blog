import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import SeoTitleIssuesList from '../SeoTitleIssuesList.vue';

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

describe('SeoTitleIssuesList.vue', () => {
    it('shows short-title issues and links to the blog editor', () => {
        const wrapper = mount(SeoTitleIssuesList, {
            props: {
                issues: [
                    {
                        type: 'post',
                        blog_id: 3,
                        post_id: 8,
                        blog_name: 'Example blog',
                        title: 'Short title',
                        length: 11,
                        edit_url: '/blogs#blog-3',
                    },
                ],
            },
        });

        expect(wrapper.text()).toContain('Example blog');
        expect(wrapper.text()).toContain('Short title');
        expect(wrapper.text()).toContain('11 blogger.seo_title_issues.length');
        expect(wrapper.find('a').attributes('href')).toBe('/blogs#blog-3');
    });

    it('shows a quiet empty state when there are no short titles', () => {
        const wrapper = mount(SeoTitleIssuesList, { props: { issues: [] } });

        expect(wrapper.text()).toContain('blogger.seo_title_issues.empty');
        expect(wrapper.text()).not.toContain('blogger.seo_title_issues.title');
        expect(wrapper.text()).not.toContain('blogger.seo_title_issues.description');
        expect(wrapper.find('ul').exists()).toBe(false);
        expect(wrapper.find('a').exists()).toBe(false);
    });
});
