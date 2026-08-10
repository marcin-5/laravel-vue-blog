import type { UserRow as UserRowType } from '@/types/admin.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import UserRow from '../UserRow.vue';

const mocks = vi.hoisted(() => ({
    patch: vi.fn(),
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { patch: mocks.patch },
}));

(global as any).route = vi.fn((_name: string, id: number) => `/admin/users/${id}`);

const user: UserRowType = {
    id: 1,
    name: 'Jane Doe',
    email: 'jane@example.com',
    role: 'blogger',
    blog_quota: 2,
};

function mountUserRow(props: Partial<InstanceType<typeof UserRow>['$props']> = {}) {
    return mount(UserRow, {
        props: {
            currentUserIsAdmin: true,
            roles: ['admin', 'blogger', 'user'],
            user,
            ...props,
        },
        global: {
            stubs: {
                Button: { template: '<button v-bind="$attrs"><slot /></button>' },
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
        },
    });
}

describe('UserRow.vue', () => {
    it('keeps save disabled until the user data changes', async () => {
        const wrapper = mountUserRow();

        expect(wrapper.find('button').attributes('disabled')).toBeDefined();

        await wrapper.find('select').setValue('admin');

        expect(wrapper.find('button').attributes('disabled')).toBeUndefined();
    });

    it('disables quota editing when the current user is not an administrator', async () => {
        const wrapper = mountUserRow({ currentUserIsAdmin: false });

        expect(wrapper.find('input[type="number"]').attributes('disabled')).toBeDefined();
    });

    it('keeps quota disabled after changing a user whose original role was not eligible', async () => {
        const wrapper = mountUserRow({ user: { ...user, role: 'user', blog_quota: null } });

        await wrapper.find('select').setValue('blogger');

        expect(wrapper.find('input[type="number"]').attributes('disabled')).toBeDefined();
    });

    it('saves the changed role and quota with the preserved Inertia visit options', async () => {
        mocks.patch.mockClear();
        const wrapper = mountUserRow();

        await wrapper.find('select').setValue('admin');
        await wrapper.find('input[type="number"]').setValue(5);
        await wrapper.find('button').trigger('click');

        expect((global as any).route).toHaveBeenCalledWith('admin.users.update', user.id);
        expect(mocks.patch).toHaveBeenCalledWith(
            `/admin/users/${user.id}`,
            { role: 'admin', blog_quota: 5 },
            expect.objectContaining({ preserveScroll: true, preserveState: true }),
        );
    });
});
