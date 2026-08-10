import type { UserRow } from '@/types/admin.types';
import { mount } from '@vue/test-utils';
import { defineComponent, type PropType } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import UserList from '../UserList.vue';
import UserRowComponent from '../UserRow.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

const users: UserRow[] = [
    { id: 1, name: 'Jane Doe', email: 'jane@example.com', role: 'blogger', blog_quota: 2 },
    { id: 2, name: 'John Doe', email: 'john@example.com', role: 'user', blog_quota: null },
];

const UserRowStub = defineComponent({
    name: 'UserRow',
    props: {
        currentUserIsAdmin: Boolean,
        roles: Array as PropType<string[]>,
        user: Object as PropType<UserRow>,
    },
    template: '<tr><td>{{ user.name }}</td></tr>',
});

describe('UserList.vue', () => {
    it('renders one row per user with the shared permissions context', () => {
        const wrapper = mount(UserList, {
            props: {
                currentUserIsAdmin: true,
                roles: ['admin', 'blogger', 'user'],
                users,
            },
            global: {
                stubs: {
                    UserRow: UserRowStub,
                },
            },
        });

        const rows = wrapper.findAllComponents(UserRowComponent);

        expect(rows).toHaveLength(users.length);
        expect(rows[0].props()).toMatchObject({
            currentUserIsAdmin: true,
            roles: ['admin', 'blogger', 'user'],
            user: users[0],
        });
    });
});
