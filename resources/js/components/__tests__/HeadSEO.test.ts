import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import HeadSEO from '../HeadSEO.vue';

const { getPage } = vi.hoisted(() => ({
    getPage: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        template: '<div><slot /></div>',
    },
    usePage: () => getPage(),
}));

describe('HeadSEO', () => {
    it('renders the Bing verification token as an escaped meta attribute', () => {
        getPage.mockReturnValue({
            props: {
                seo: {
                    bingSiteVerificationToken: 'token-"<script>alert(1)</script>',
                },
            },
        });

        const wrapper = mount(HeadSEO);

        expect(wrapper.find('meta[name="msvalidate.01"]').attributes('content')).toBe('token-"<script>alert(1)</script>');
        expect(wrapper.find('script').exists()).toBe(false);
    });

    it('omits the Bing verification meta tag when no token is provided', () => {
        getPage.mockReturnValue({
            props: {
                seo: {},
            },
        });

        const wrapper = mount(HeadSEO);

        expect(wrapper.find('meta[name="msvalidate.01"]').exists()).toBe(false);
    });
});
