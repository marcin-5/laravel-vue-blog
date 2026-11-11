import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PostBackLink from '../PostBackLink.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

describe('PostBackLink.vue', () => {
    it('returns to the previous page when clicked', async () => {
        const historyBack = vi.spyOn(window.history, 'back').mockImplementation(() => {});
        const wrapper = mount(PostBackLink);

        expect(wrapper.text()).toContain('blog.post_nav.back');

        await wrapper.get('button').trigger('click');

        expect(historyBack).toHaveBeenCalledOnce();

        historyBack.mockRestore();
    });
});
