import type { Navigation } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import BlogPostNav from '../BlogPostNav.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        name: 'Link',
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
}));

vi.mock('@/components/blog/BorderDivider.vue', () => ({
    default: { name: 'BorderDivider', template: '<hr />' },
}));

const mountNavigation = (navigation: Navigation) => mount(BlogPostNav, { props: { navigation } });

describe('BlogPostNav.vue', () => {
    it('shows recommended links and the latest post without inactive navigation on the blog landing page', () => {
        const wrapper = mountNavigation({
            landingUrl: '/',
            isLandingPage: true,
            recommendedLinksUrl: '/recommended',
            nextPost: { title: 'Newest post', slug: 'newest-post', url: '/newest-post' },
        });
        const desktop = wrapper.find('.desktop-navigation');
        const mobile = wrapper.find('.mobile-navigation');

        expect(desktop.findAll('a').map((link) => link.attributes('href'))).toEqual(['/recommended', '/newest-post']);
        expect(desktop.text()).toContain('blog.post_nav.latest_post');
        expect(desktop.text()).not.toContain('blog.post_nav.previous');
        expect(desktop.text()).not.toContain('blog.post_nav.home_page');
        expect(mobile.findAll('a').map((link) => link.attributes('href'))).toEqual(['/newest-post', '/recommended']);
        expect(mobile.text()).toContain('blog.post_nav.latest_post');
    });

    it('keeps the next-post label and post navigation on other blog pages', () => {
        const wrapper = mountNavigation({
            landingUrl: '/',
            isLandingPage: false,
            recommendedLinksUrl: '/recommended',
            prevPost: { title: 'Previous post', slug: 'previous-post', url: '/previous-post' },
            nextPost: { title: 'Next post', slug: 'next-post', url: '/next-post' },
        });
        const desktop = wrapper.find('.desktop-navigation');
        const mobile = wrapper.find('.mobile-navigation');

        expect(desktop.text()).toContain('blog.post_nav.next');
        expect(desktop.text()).not.toContain('blog.post_nav.latest_post');
        expect(desktop.text()).toContain('blog.post_nav.home_page');
        expect(desktop.find('a[href="/recommended"]').exists()).toBe(true);
        expect(mobile.text()).toContain('blog.post_nav.next');
        expect(mobile.text()).not.toContain('blog.post_nav.latest_post');
        expect(mobile.find('a[href="/recommended"]').exists()).toBe(true);
    });

    it('does not show recommended links when none are available', () => {
        const wrapper = mountNavigation({
            landingUrl: '/',
            isLandingPage: true,
            recommendedLinksUrl: null,
            nextPost: { title: 'Newest post', slug: 'newest-post', url: '/newest-post' },
        });
        const desktop = wrapper.find('.desktop-navigation');

        expect(wrapper.findAll('a[href="/recommended"]')).toHaveLength(0);
        expect(wrapper.text()).not.toContain('blog.recommended_links.heading');
        expect(desktop.findAll('a').map((link) => link.attributes('href'))).toEqual(['/newest-post']);
        expect(desktop.find('a[href="/newest-post"]').classes()).toContain('ml-auto');
        expect(desktop.text()).not.toContain('blog.post_nav.previous');
        expect(desktop.text()).not.toContain('blog.post_nav.home_page');
    });
});
