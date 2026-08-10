import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';
import AddGroupMemberForm from '../AddGroupMemberForm.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

const Select = defineComponent({
    name: 'SelectStub',
    props: { modelValue: { type: String, default: null } },
    emits: ['update:modelValue'],
    template: '<select :value="modelValue ?? \'\'" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
});

function mountForm(groupId = 'all') {
    return mount(AddGroupMemberForm, {
        props: { groupId },
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

describe('AddGroupMemberForm.vue', () => {
    it('keeps submission disabled without a concrete group or email', async () => {
        const wrapper = mountForm();
        const button = wrapper.find('button');

        expect(button.attributes('disabled')).toBeDefined();

        await wrapper.setProps({ groupId: '4' });
        expect(button.attributes('disabled')).toBeDefined();

        await wrapper.find('input[type="email"]').setValue('member@example.com');
        expect(button.attributes('disabled')).toBeUndefined();
    });

    it('emits a trimmed email and selected role and clears the email on success', async () => {
        const wrapper = mountForm('4');

        await wrapper.find('input[type="email"]').setValue('  member@example.com  ');
        await wrapper.find('select').setValue('moderator');
        await wrapper.find('form').trigger('submit');

        const submissions = wrapper.emitted('submit');
        expect(submissions).toHaveLength(1);
        expect(submissions?.[0]?.[0]).toEqual({ email: 'member@example.com', role: 'moderator' });

        const onSuccess = submissions?.[0]?.[1];
        expect(onSuccess).toEqual(expect.any(Function));
        (onSuccess as () => void)();
        await nextTick();
        expect((wrapper.find('input[type="email"]').element as HTMLInputElement).value).toBe('');
    });
});
