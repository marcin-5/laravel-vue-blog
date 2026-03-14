import type { AdminBlog as Blog } from '@/types/blog.types';
import { describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';
import { useBlogFormLogic } from '../useBlogFormLogic';

// Mock @inertiajs/vue3
vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data) => ({
        ...data,
        processing: false,
        errors: {},
    })),
}));

describe('useBlogFormLogic', () => {
    it('initializes form with blog data', () => {
        const blog: Partial<Blog> = {
            id: 1,
            name: 'Test Blog',
        };

        const { form } = useBlogFormLogic({ blog: blog as Blog });

        expect(form.name).toBe('Test Blog');
    });

    it('sets correct fieldIdPrefix for create mode', () => {
        const { fieldIdPrefix } = useBlogFormLogic({ isEdit: false });
        expect(fieldIdPrefix.value).toBe('create-blog-new');
    });

    it('sets correct fieldIdPrefix for edit mode', () => {
        const blog: Partial<Blog> = { id: 123 };
        const { fieldIdPrefix } = useBlogFormLogic({ isEdit: true, blog: blog as Blog });
        expect(fieldIdPrefix.value).toBe('edit-blog-123');
    });

    it('updates categories in form', () => {
        const { form, updateCategories } = useBlogFormLogic();
        updateCategories([1, 2, 3]);
        expect(form.categories).toEqual([1, 2, 3]);
    });

    it('uses external form if provided', () => {
        const externalForm = { name: 'External' } as any;
        const { form } = useBlogFormLogic({ externalForm });
        expect(form).toBe(externalForm);
    });

    it('watches for blog changes and updates form', async () => {
        const blog1: Partial<Blog> = { id: 1, name: 'Blog 1' };
        const blog2: Partial<Blog> = { id: 2, name: 'Blog 2' };

        const options = reactive({ blog: blog1 as Blog });
        const { form } = useBlogFormLogic(options);

        expect(form.name).toBe('Blog 1');

        options.blog = blog2 as Blog;
        await nextTick();

        expect(form.name).toBe('Blog 2');
    });
});
