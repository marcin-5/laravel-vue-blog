import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import RecommendedLinkSections from '../RecommendedLinkSections.vue';

const mocks = vi.hoisted(() => ({
    put: vi.fn(),
    toast: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { put: mocks.put, delete: vi.fn() },
    useForm: (data: Record<string, unknown>) => ({
        ...data,
        errors: {},
        processing: false,
        clearErrors: vi.fn(),
        patch: vi.fn(),
        reset: vi.fn(),
    }),
}));

vi.mock('@/composables/useToast', () => ({
    useToast: () => ({ toast: mocks.toast }),
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

const initialLinks = [
    { id: 1, url: 'https://one.example', name: 'Link one', description: null, category_id: null, display_order: 0 },
    { id: 2, url: 'https://two.example', name: 'Link two', description: null, category_id: null, display_order: 1 },
];

describe('RecommendedLinkSections.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('sends an Inertia reorder and restores the previous link order on validation errors', async () => {
        const route = vi.fn((name: string, id: number) => `${name}/${id}`);
        (global as any).route = route;
        const wrapper = mount(RecommendedLinkSections, {
            props: { blogId: 7, categories: [], recommendedLinks: initialLinks },
            global: { mocks: { route } },
        });

        await wrapper.findAll('button[aria-label="blogger.recommended_links.move_up"]')[1].trigger('click');

        expect(wrapper.findAll('a.font-medium.break-all').map((link) => link.text())).toEqual(['Link two', 'Link one']);
        expect(route).toHaveBeenCalledWith('blogger.recommended-links.reorder', 7);
        expect(router.put).toHaveBeenCalledWith(
            'blogger.recommended-links.reorder/7',
            { category_id: null, link_ids: [2, 1] },
            expect.objectContaining({ only: ['recommendedLinks'], preserveScroll: true, onError: expect.any(Function) }),
        );

        const visitOptions = vi.mocked(router.put).mock.calls[0][2];
        visitOptions!.onError!({ link_ids: 'Invalid order.' });
        await nextTick();

        expect(wrapper.findAll('a.font-medium.break-all').map((link) => link.text())).toEqual(['Link one', 'Link two']);
        expect(mocks.toast).toHaveBeenCalledWith({
            title: 'blogger.recommended_links.reorder_failed',
            variant: 'destructive',
        });
    });
});