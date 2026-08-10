import type { GroupMember } from '@/types/group-members.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import GroupMembersTable from '../GroupMembersTable.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

const Select = defineComponent({
    name: 'SelectStub',
    props: { modelValue: { type: String, default: null } },
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

function mountTable(members: GroupMember[]) {
    return mount(GroupMembersTable, {
        props: { members },
        global: {
            stubs: {
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

describe('GroupMembersTable.vue', () => {
    it('renders one row per member and forwards row actions', async () => {
        const wrapper = mountTable([member]);

        expect(wrapper.findAll('tbody tr')).toHaveLength(1);
        expect(wrapper.text()).toContain(member.email);

        await wrapper.find('select').setValue('moderator');
        expect(wrapper.emitted('roleChange')).toEqual([[member, 'moderator']]);

        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('remove')).toEqual([[member]]);
    });

    it('renders an accessible empty state when there are no members', () => {
        const wrapper = mountTable([]);

        expect(wrapper.find('tbody tr').text()).toContain('list.no_results');
        expect(wrapper.find('td').attributes('colspan')).toBe('5');
    });
});
