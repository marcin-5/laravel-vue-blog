import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ThemeManagementForm from '../ThemeManagementForm.vue';
import { useForm } from '@inertiajs/vue3';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue');

    return {
        useForm: vi.fn((data) =>
            Object.assign(reactive(data), {
                processing: false,
                errors: {},
                patch: vi.fn(),
                clearErrors: vi.fn(),
            }),
        ),
    };
});

vi.mock('@/components/blogger/FormSelectField.vue', () => ({
    default: {
        name: 'FormSelectField',
        props: ['modelValue', 'options'],
        emits: ['update:modelValue'],
        template: `<div><button v-for="option in options" :key="option.value" type="button" @click="$emit('update:modelValue', String(option.value))">{{ option.label }}</button></div>`,
    },
}));

vi.mock('@/components/blogger/EntityThemeSection.vue', () => ({
    default: {
        name: 'EntityThemeSection',
        props: ['modelValue'],
        template: '<div data-testid="theme">{{ JSON.stringify(modelValue) }}</div>',
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        template: '<button v-bind="$attrs"><slot /></button>',
    },
}));

const routeMock = vi.fn((name: string, id: number) => `${name}/${id}`);
(global as any).route = routeMock;

describe('ThemeManagementForm.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('loads the selected blog theme and saves only the selected blog', async () => {
        const firstTheme = { light: { '--background': '#ffffff' } };
        const secondTheme = { light: { '--background': '#eeeeee' } };
        const wrapper = mount(ThemeManagementForm, {
            props: {
                entityType: 'blog',
                entities: [
                    { id: 1, name: 'First blog', theme: firstTheme },
                    { id: 2, name: 'Second blog', theme: secondTheme },
                ],
            },
        });

        expect(wrapper.get('[data-testid="theme"]').text()).toBe(JSON.stringify(firstTheme));

        await wrapper.findAll('button')[1].trigger('click');
        await wrapper.get('form').trigger('submit');

        expect(wrapper.get('[data-testid="theme"]').text()).toBe(JSON.stringify(secondTheme));
        expect(routeMock).toHaveBeenCalledWith('blogs.update', 2);
        expect(vi.mocked(useForm).mock.results[0].value.patch).toHaveBeenCalledWith('blogs.update/2', { preserveScroll: true });
    });

    it('uses the group update route for a selected group', async () => {
        const wrapper = mount(ThemeManagementForm, {
            props: {
                entityType: 'group',
                entities: [{ id: 7, name: 'Group', theme: { dark: { '--background': '#111111' } } }],
            },
        });

        await wrapper.get('form').trigger('submit');

        expect(routeMock).toHaveBeenCalledWith('blogger.groups.content.update', 7);
        expect(vi.mocked(useForm).mock.results[0].value.patch).toHaveBeenCalledWith('blogger.groups.content.update/7', {
            preserveScroll: true,
        });
    });
});