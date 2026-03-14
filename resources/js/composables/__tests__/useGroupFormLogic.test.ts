import type { AdminGroup as Group } from '@/types/blog.types';
import { describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';
import { useGroupFormLogic } from '../useGroupFormLogic';

// Mock @inertiajs/vue3
vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data) => ({
        ...data,
        processing: false,
        errors: {},
    })),
}));

describe('useGroupFormLogic', () => {
    it('initializes form with group data', () => {
        const group: Partial<Group> = {
            id: 1,
            name: 'Test Group',
        };

        const { form } = useGroupFormLogic({ group: group as Group });

        expect(form.name).toBe('Test Group');
    });

    it('sets correct fieldIdPrefix for create mode', () => {
        const { fieldIdPrefix } = useGroupFormLogic({ isEdit: false });
        expect(fieldIdPrefix.value).toBe('create-group-new');
    });

    it('sets correct fieldIdPrefix for edit mode', () => {
        const group: Partial<Group> = { id: 123 };
        const { fieldIdPrefix } = useGroupFormLogic({ isEdit: true, group: group as Group });
        expect(fieldIdPrefix.value).toBe('edit-group-123');
    });

    it('uses external form if provided', () => {
        const externalForm = { name: 'External' } as any;
        const { form } = useGroupFormLogic({ externalForm });
        expect(form).toBe(externalForm);
    });

    it('watches for group changes and updates form', async () => {
        const group1: Partial<Group> = { id: 1, name: 'Group 1' };
        const group2: Partial<Group> = { id: 2, name: 'Group 2' };

        const options = reactive({ group: group1 as Group });
        const { form } = useGroupFormLogic(options);

        expect(form.name).toBe('Group 1');

        options.group = group2 as Group;
        await nextTick();

        expect(form.name).toBe('Group 2');
    });
});
