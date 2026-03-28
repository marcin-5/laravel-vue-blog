import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import GroupForm from '../GroupForm.vue';

// Mock dependencies
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data) => ({
        ...data,
        processing: false,
        errors: {},
        patch: vi.fn().mockResolvedValue({}),
    })),
    useHttp: () => ({
        post: vi.fn().mockResolvedValue({ data: { html: '<p></p>' } }),
        get: vi.fn().mockResolvedValue({ data: {} }),
        delete: vi.fn().mockResolvedValue({}),
    }),
}));

// Mock components to simplify testing
vi.mock('@/components/blogger/EntityMarkdownField.vue', () => ({
    default: { name: 'EntityMarkdownField', template: '<div>EntityMarkdownField</div>' },
}));
vi.mock('@/components/blogger/EntityThemeSection.vue', () => ({
    default: { name: 'EntityThemeSection', template: '<div>EntityThemeSection</div>' },
}));
vi.mock('@/components/blogger/FormPublishingSettings.vue', () => ({
    default: { name: 'FormPublishingSettings', template: '<div>FormPublishingSettings</div>' },
}));
vi.mock('@/components/blogger/FormSubmitActions.vue', () => ({
    default: { name: 'FormSubmitActions', template: '<div>FormSubmitActions</div>' },
}));
vi.mock('@/components/blogger/PostFormField.vue', () => ({
    default: { name: 'PostFormField', template: '<div>PostFormField</div>' },
}));

// Mock route function
(global as any).route = vi.fn(() => 'mock-route');

describe('GroupForm.vue', () => {
    const defaultProps = {
        isEdit: false,
    };

    it('renders the form', () => {
        const wrapper = mount(GroupForm, {
            props: defaultProps,
        });
        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('emits submit when handleSubmit is called', async () => {
        const wrapper = mount(GroupForm, {
            props: defaultProps,
        });

        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')).toBeTruthy();
        expect(wrapper.emitted('submit')![0][0]).toBeDefined();
    });

    it('emits cancel when handleCancel is called', async () => {
        const wrapper = mount(GroupForm, {
            props: defaultProps,
        });

        const submitActions = wrapper.findComponent({ name: 'FormSubmitActions' });
        await submitActions.vm.$emit('cancel');

        expect(wrapper.emitted('cancel')).toBeTruthy();
    });

    it('calls form.patch when handleApply is called in edit mode', async () => {
        const group = { id: 5, name: 'Test Group' };
        const wrapper = mount(GroupForm, {
            props: {
                ...defaultProps,
                isEdit: true,
                group: group as any,
            },
        });

        const submitActions = wrapper.findComponent({ name: 'FormSubmitActions' });
        await submitActions.vm.$emit('apply');

        expect((global as any).route).toHaveBeenCalledWith('blogger.groups.content.update', 5);
    });
});
