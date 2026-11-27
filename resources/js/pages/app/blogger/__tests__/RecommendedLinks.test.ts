import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import RecommendedLinks from '../RecommendedLinks.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { props: ['title'], template: '<span />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (data: Record<string, unknown>) => ({
        ...data,
        errors: {},
        processing: false,
        post: vi.fn(),
        reset: vi.fn(),
    }),
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

vi.mock('@/components/blogger/RecommendedLinkSections.vue', () => ({
    default: { template: '<div />' },
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

describe('RecommendedLinks.vue', () => {
    it('includes navigation back to the blogs list', () => {
        const route = vi.fn((name: string, id?: number) => (id ? `${name}/${id}` : name));
        (global as any).route = route;
        const wrapper = mount(RecommendedLinks, {
            props: {
                blog: { id: 6, name: 'Example blog' },
                categories: [],
                recommendedLinks: [],
            },
            global: { mocks: { route } },
        });

        expect(wrapper.find('a[href="blogs.index"]').text()).toContain('blogger.recommended_links.back_to_blogs');
    });
});