import AddGroupMemberForm from '@/pages/app/blogger/components/AddGroupMemberForm.vue';
import GroupMembersFilters from '@/pages/app/blogger/components/GroupMembersFilters.vue';
import GroupMembersPagination from '@/pages/app/blogger/components/GroupMembersPagination.vue';
import GroupMembersTable from '@/pages/app/blogger/components/GroupMembersTable.vue';
import type { GroupMember } from '@/types/group-members.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, shallowRef } from 'vue';
import GroupMembers from '../GroupMembers.vue';

const mocks = vi.hoisted(() => ({
    useGroupMembers: vi.fn(),
    route: vi.fn((name: string) => `/${name}`),
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/composables/useGroupMembers', () => ({
    useGroupMembers: mocks.useGroupMembers,
}));

vi.stubGlobal('route', mocks.route);

const Select = defineComponent({
    name: 'SelectStub',
    props: { modelValue: { type: [String, Number], default: null } },
    emits: ['update:modelValue'],
    template: '<select :value="modelValue ?? \'\'" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
});

const member: GroupMember = {
    id: 12,
    name: 'Jane Doe',
    email: 'jane@example.com',
    group_id: 4,
    role: 'member',
    joined_at: '2026-08-10',
};

const defaultProps = {
    filters: { group_id: null, per_page: 10 as const, sort_by: 'email' as const, sort_dir: 'asc' as const },
    groups: [{ id: 4, name: 'Tech' }],
    isAdmin: true,
    members: [member],
    owners: [{ id: 7, name: 'Jane Doe', email: 'jane@example.com' }],
    pagination: undefined,
};

function setComposableState() {
    mocks.useGroupMembers.mockReturnValue({
        groupId: shallowRef('all'),
        ownerId: shallowRef(null),
        perPage: shallowRef('10'),
        sortBy: shallowRef('email'),
        sortDir: shallowRef('asc'),
        changeGroup: vi.fn(),
        changeOwner: vi.fn(),
        changePerPage: vi.fn(),
        changeRole: vi.fn(),
        changeSortBy: vi.fn(),
        changeSortDir: vi.fn(),
        addMember: vi.fn(),
        removeMember: vi.fn(),
        visitPage: vi.fn(),
    });
}

function mountPage(props = defaultProps) {
    setComposableState();

    return mount(GroupMembers, {
        props,
        global: {
            stubs: {
                AppLayout: { name: 'AppLayout', template: '<div><slot /></div>' },
                Button: { template: '<button v-bind="$attrs"><slot /></button>' },
                Select,
                SelectContent: { template: '<slot />' },
                SelectItem: { template: '<option :value="$attrs.value"><slot /></option>' },
                SelectTrigger: { template: '<span><slot /></span>' },
                SelectValue: { template: '<span />' },
            },
        },
    });
}

describe('GroupMembers.vue', () => {
    it('composes filters, add form, table, and pagination with page props', () => {
        const wrapper = mountPage();

        expect(wrapper.findComponent(GroupMembersFilters).props()).toMatchObject({
            groups: defaultProps.groups,
            isAdmin: true,
            owners: defaultProps.owners,
        });
        expect(wrapper.findComponent(AddGroupMemberForm).props('groupId')).toBe('all');
        expect(wrapper.findComponent(GroupMembersTable).props('members')).toEqual(defaultProps.members);
        expect(wrapper.findComponent(GroupMembersPagination).props('pagination')).toBeUndefined();
        expect(mocks.route).toHaveBeenCalledWith('dashboard');
        expect(mocks.route).toHaveBeenCalledWith('blogger.groups.members.index');
    });

    it('shows the owner selector for administrators and an empty state for empty lists', () => {
        const adminWrapper = mountPage();
        expect(adminWrapper.text()).toContain('list.owner');

        const bloggerWrapper = mountPage({ ...defaultProps, isAdmin: false, members: [], owners: [] });
        expect(bloggerWrapper.text()).not.toContain('list.owner');
        expect(bloggerWrapper.text()).toContain('list.no_results');
    });
});
