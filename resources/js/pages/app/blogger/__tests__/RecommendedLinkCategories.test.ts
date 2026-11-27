import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import RecommendedLinkCategories from '../RecommendedLinkCategories.vue';

const mocks = vi.hoisted(() => ({
    put: vi.fn(),
    toast: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { props: ['title'], template: '<span />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { put: mocks.put, delete: vi.fn() },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

vi.mock('@/components/blogger/RecommendedLinkCategoryForm.vue', () => ({
    default: { template: '<div />' },
}));

vi.mock('@/components/blogger/RecommendedLinkCategoryList.vue', () => ({
    default: {
        name: 'RecommendedLinkCategoryList',
        props: ['categories', 'processing'],
        emits: ['move', 'edit', 'delete'],
        template: '<ol><li v-for="category in categories" :key="category.id">{{ category.name }}</li></ol>',
    },
}));

vi.mock('@/composables/useToast', () => ({
    useToast: () => ({ toast: mocks.toast }),
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

describe('RecommendedLinkCategories.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('uses optimistic category ordering and rolls back when Inertia returns validation errors', async () => {
        const route = vi.fn((name: string, id: number) => `${name}/${id}`);
        (global as any).route = route;
        const wrapper = mount(RecommendedLinkCategories, {
            props: {
                blog: { id: 4, name: 'Example blog' },
                categories: [
                    { id: 1, name: 'First', display_order: 0 },
                    { id: 2, name: 'Second', display_order: 1 },
                ],
            },
            global: { mocks: { route } },
        });

        const list = wrapper.findComponent({ name: 'RecommendedLinkCategoryList' });
        list.vm.$emit('move', 1, -1);
        await nextTick();

        expect(list.props('categories').map((category: { name: string }) => category.name)).toEqual(['Second', 'First']);
        expect(router.put).toHaveBeenCalledWith(
            'blogger.recommended-link-categories.reorder/4',
            { category_ids: [2, 1] },
            expect.objectContaining({ only: ['categories'], preserveScroll: true, onError: expect.any(Function) }),
        );

        const visitOptions = vi.mocked(router.put).mock.calls[0][2];
        visitOptions!.onError!({ category_ids: 'Invalid order.' });
        await nextTick();

        expect(list.props('categories').map((category: { name: string }) => category.name)).toEqual(['First', 'Second']);
        expect(mocks.toast).toHaveBeenCalledWith({
            title: 'blogger.recommended_link_categories.reorder_failed',
            variant: 'destructive',
        });
    });
});