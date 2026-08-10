import type { GroupMemberSortDirection, GroupMemberSortField, GroupOwner, SimpleGroup } from '@/types/group-members.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import GroupMembersFilters from '../GroupMembersFilters.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

const Select = defineComponent({
    name: 'SelectStub',
    props: { modelValue: { type: [String, Number], default: null } },
    emits: ['update:modelValue'],
    template: '<select :value="modelValue ?? \'\'" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
});

const defaultProps = {
    filters: {
        groupId: 'all',
        ownerId: null,
        perPage: '10',
        sortBy: 'email' as GroupMemberSortField,
        sortDir: 'asc' as GroupMemberSortDirection,
    },
    groups: [{ id: 1, name: 'Tech' }] satisfies SimpleGroup[],
    owners: [{ id: 7, name: 'Jane Doe', email: 'jane@example.com' }] satisfies GroupOwner[],
};

function mountFilters(isAdmin: boolean) {
    return mount(GroupMembersFilters, {
        props: { ...defaultProps, isAdmin },
        global: {
            stubs: {
                Select,
                SelectContent: { template: '<slot />' },
                SelectItem: { template: '<option :value="$attrs.value"><slot /></option>' },
                SelectTrigger: { template: '<span><slot /></span>' },
                SelectValue: { template: '<span />' },
            },
        },
    });
}

describe('GroupMembersFilters.vue', () => {
    it('renders the owner selector only for administrators', () => {
        expect(mountFilters(true).text()).toContain('list.owner');
        expect(mountFilters(false).text()).not.toContain('list.owner');
    });

    it('emits typed filter changes from each selector', async () => {
        const wrapper = mountFilters(true);
        const selects = wrapper.findAll('select');

        await selects[0].setValue('7');
        await selects[1].setValue('1');
        await selects[2].setValue('25');
        await selects[3].setValue('joined_at');
        await selects[4].setValue('desc');

        expect(wrapper.emitted('ownerChange')).toEqual([['7']]);
        expect(wrapper.emitted('groupChange')).toEqual([['1']]);
        expect(wrapper.emitted('perPageChange')).toEqual([['25']]);
        expect(wrapper.emitted('sortByChange')).toEqual([['joined_at']]);
        expect(wrapper.emitted('sortDirChange')).toEqual([['desc']]);
    });
});
