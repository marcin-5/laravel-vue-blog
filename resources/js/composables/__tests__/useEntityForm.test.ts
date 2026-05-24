import { describe, expect, it, vi } from 'vitest';
import { useEntityForm } from '../useEntityForm';

// Mock useI18n
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        locale: { value: 'en' },
    }),
}));

// Mock @inertiajs/vue3
const postMock = vi.fn();
const patchMock = vi.fn();
const resetMock = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data) => ({
        ...data,
        post: postMock,
        patch: patchMock,
        reset: resetMock,
        processing: false,
        errors: {},
    })),
}));

describe('useEntityForm', () => {
    const options = {
        createDefaultData: (locale: string) => ({ name: '', locale }),
        populateFromEntity: (form: any, entity: any) => {
            form.name = entity.name;
        },
        storeRoute: '/entities',
        updateRoute: (id: number) => `/entities/${id}`,
    };

    it('initializes with default state', () => {
        const { showCreate, editingId, createForm, editForm } = useEntityForm(options);

        expect(showCreate.value).toBe(false);
        expect(editingId.value).toBe(null);
        expect(createForm.name).toBe('');
        expect(createForm.locale).toBe('en');
        expect(editForm.name).toBe('');
    });

    it('handles open and close create form', () => {
        const { showCreate, openCreateForm, closeCreateForm } = useEntityForm(options);

        openCreateForm();
        expect(showCreate.value).toBe(true);

        closeCreateForm();
        expect(showCreate.value).toBe(false);
        expect(resetMock).toHaveBeenCalled();
    });

    it('handles start and cancel edit', () => {
        const { editingId, editForm, startEdit, cancelEdit } = useEntityForm(options);
        const entity = { id: 1, name: 'Test' };

        startEdit(entity);
        expect(editingId.value).toBe(1);
        expect(editForm.name).toBe('Test');

        cancelEdit();
        expect(editingId.value).toBe(null);
        expect(resetMock).toHaveBeenCalled();
    });

    it('toggles edit off if starting edit on already editing entity', () => {
        const { editingId, startEdit } = useEntityForm(options);
        const entity = { id: 1, name: 'Test' };

        startEdit(entity);
        expect(editingId.value).toBe(1);

        startEdit(entity);
        expect(editingId.value).toBe(null);
    });

    it('submits create form', () => {
        const { submitCreate } = useEntityForm(options);
        submitCreate();
        expect(postMock).toHaveBeenCalledWith('/entities', expect.any(Object));
    });

    it('submits edit form', () => {
        const { submitEdit } = useEntityForm(options);
        const entity = { id: 1, name: 'Test' };
        submitEdit(entity);
        expect(patchMock).toHaveBeenCalledWith('/entities/1', expect.any(Object));
    });
});
