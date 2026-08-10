import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, reactive } from 'vue';
import UserCreateForm from '../UserCreateForm.vue';

const mocks = vi.hoisted(() => ({
    forms: [] as any[],
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data) => {
        const form = reactive({
            ...data,
            errors: {},
            processing: false,
            post: vi.fn(),
            reset: vi.fn(),
            transform: vi.fn((callback) => {
                form.post.mockImplementation((url: string, options: Record<string, unknown>) => {
                    form.lastPayload = callback();
                    form.lastUrl = url;
                    form.lastOptions = options;
                });

                return form;
            }),
        });

        mocks.forms.push(form);

        return form;
    }),
}));

(global as any).route = vi.fn(() => '/admin/users');

function mountUserCreateForm(currentUserIsAdmin = true) {
    return mount(UserCreateForm, {
        props: {
            currentUserIsAdmin: currentUserIsAdmin,
            roles: ['admin', 'blogger', 'user'],
        },
        global: {
            stubs: {
                Button: { template: '<button v-bind="$attrs"><slot /></button>' },
                InputError: true,
                Select: defineComponent({
                    props: {
                        modelValue: String,
                    },
                    emits: ['update:modelValue'],
                    template:
                        '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><option value="admin">admin</option><option value="blogger">blogger</option><option value="user">user</option></select>',
                }),
                SelectContent: { template: '<div><slot /></div>' },
                SelectItem: { template: '<option :value="$attrs.value"><slot /></option>' },
                SelectTrigger: { template: '<div><slot /></div>' },
                SelectValue: { template: '<span />' },
            },
            mocks: {
                $t: (key: string) => key,
            },
        },
    });
}

describe('UserCreateForm.vue', () => {
    it('sends the quota for an administrator creating a blogger', async () => {
        const wrapper = mountUserCreateForm();
        const form = mocks.forms.at(-1);

        await wrapper.find('#new-name').setValue('Jane Doe');
        await wrapper.find('#new-email').setValue('jane@example.com');
        await wrapper.find('#new-password').setValue('secret-password');
        await wrapper.find('select').setValue('blogger');
        await wrapper.find('#new-blog-quota').setValue(3);
        await wrapper.find('form').trigger('submit');

        expect(form.lastPayload).toEqual({
            name: 'Jane Doe',
            email: 'jane@example.com',
            password: 'secret-password',
            role: 'blogger',
            blog_quota: 3,
        });
        expect(form.lastOptions).toMatchObject({ preserveScroll: true, preserveState: true });
    });

    it('does not send the quota when the administrator creates a regular user', async () => {
        const wrapper = mountUserCreateForm();
        const form = mocks.forms.at(-1);

        await wrapper.find('form').trigger('submit');

        expect(form.lastPayload).not.toHaveProperty('blog_quota');
    });

    it('does not send the quota when the current user is not an administrator', async () => {
        const wrapper = mountUserCreateForm(false);
        const form = mocks.forms.at(-1);

        await wrapper.find('select').setValue('blogger');
        await wrapper.find('form').trigger('submit');

        expect(wrapper.find('#new-blog-quota').attributes('disabled')).toBeDefined();
        expect(form.lastPayload).not.toHaveProperty('blog_quota');
    });

    it('resets the form to the regular-user defaults after a successful save', async () => {
        const wrapper = mountUserCreateForm();
        const form = mocks.forms.at(-1);

        await wrapper.find('select').setValue('blogger');
        await wrapper.find('#new-blog-quota').setValue(3);
        await wrapper.find('form').trigger('submit');
        (form.lastOptions.onSuccess as () => void)();

        expect(form.reset).toHaveBeenCalledOnce();
        expect(form.role).toBe('user');
        expect(form.blog_quota).toBe(0);
    });
});
