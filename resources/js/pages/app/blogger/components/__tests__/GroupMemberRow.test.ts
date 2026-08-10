import type { GroupMember } from '@/types/group-members.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import GroupMemberRow from '../GroupMemberRow.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

const member: GroupMember = {
    id: 12,
    name: 'Jane Doe',
    email: 'jane@example.com',
    group_id: 4,
    role: 'member',
    joined_at: '2026-08-10',
};

const Select = defineComponent({
    name: 'SelectStub',
    props: { modelValue: { type: String, default: null } },
    emits: ['update:modelValue'],
    template: '<select :value="modelValue ?? \'\'" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
});

function mountRow() {
    return mount(GroupMemberRow, {
        props: { member },
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

describe('GroupMemberRow.vue', () => {
    it('renders member details and emits role changes with the member', async () => {
        const wrapper = mountRow();

        expect(wrapper.text()).toContain('jane@example.com');
        expect(wrapper.text()).toContain('Jane Doe');
        expect(wrapper.text()).toContain('2026-08-10');

        await wrapper.find('select').setValue('maintainer');

        expect(wrapper.emitted('roleChange')).toEqual([[member, 'maintainer']]);
    });

    it('emits the complete member when removal is requested', async () => {
        const wrapper = mountRow();

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('remove')).toEqual([[member]]);
    });
});
