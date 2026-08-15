import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PostBackLink from '../PostBackLink.vue';

const visit = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    router: {
        visit: (...args: unknown[]) => visit(...args),
    },
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

describe('PostBackLink.vue', () => {
    it('navigates to the provided landing url when clicked', async () => {
        const wrapper = mount(PostBackLink, { props: { landingUrl: 'https://blog.test' } });

        expect(wrapper.text()).toContain('blog.post_nav.back');

        await wrapper.get('button').trigger('click');

        expect(visit).toHaveBeenCalledWith('https://blog.test');
    });
});
