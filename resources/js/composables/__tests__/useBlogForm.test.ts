import { describe, expect, it, vi } from 'vitest';
import { useBlogForm } from '../useBlogForm';

// Mock route
(global as any).route = vi.fn((name) => name);

// Mock useI18n
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        locale: { value: 'en' },
    }),
}));

// Mock @inertiajs/vue3
const postMock = vi.fn();
const patchMock = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data) => ({
        ...data,
        post: postMock,
        patch: patchMock,
        reset: vi.fn(),
        processing: false,
        errors: {},
    })),
}));

describe('useBlogForm', () => {
    it('initializes with blog form specifics', () => {
        const { createForm, submitCreate, submitEdit } = useBlogForm();

        expect(createForm.sidebar).toBe(0);
        expect(createForm.page_size).toBe(10);

        submitCreate();
        expect(postMock).toHaveBeenCalledWith('blogs.store', expect.any(Object));

        submitEdit({ id: 1 } as any);
        expect(patchMock).toHaveBeenCalledWith('blogs.update', expect.any(Object));
    });
});
