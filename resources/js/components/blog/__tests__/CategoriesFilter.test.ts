import type { CategoryItem } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CategoriesFilter from '../CategoriesFilter.vue';

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', inheritAttrs: false, template: '<button v-bind="$attrs"><slot /></button>' },
}));

vi.mock('../CategoryPill.vue', () => ({
    default: {
        name: 'CategoryPill',
        props: ['label', 'selected'],
        emits: ['click'],
        template: '<button type="button" @click="$emit(\'click\')">{{ label }}</button>',
    },
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

describe('CategoriesFilter.vue', () => {
    const categories: CategoryItem[] = [
        { id: 2, name: 'Vue' },
        { id: 4, name: 'Laravel' },
    ];

    it('renders categories with their selected state', () => {
        const wrapper = mount(CategoriesFilter, {
            props: { categories, selectedIds: [2], clearLabel: 'Clear' },
        });
        const pills = wrapper.findAllComponents({ name: 'CategoryPill' });

        expect(pills).toHaveLength(2);
        expect(pills[0]?.props()).toMatchObject({ label: 'Vue', selected: true });
        expect(pills[1]?.props()).toMatchObject({ label: 'Laravel', selected: false });
        expect(wrapper.findAll('button').at(2)?.text()).toBe('Clear');
    });

    it('emits category IDs and clear actions', async () => {
        const wrapper = mount(CategoriesFilter, {
            props: { categories, selectedIds: [2], clearLabel: 'Clear' },
        });
        const pills = wrapper.findAllComponents({ name: 'CategoryPill' });
        const clearButton = wrapper.findAll('button').at(2);

        pills[1]?.vm.$emit('click');
        await clearButton?.trigger('click');

        expect(wrapper.emitted('toggle')).toEqual([[4]]);
        expect(wrapper.emitted('clear')).toHaveLength(1);
    });
});
