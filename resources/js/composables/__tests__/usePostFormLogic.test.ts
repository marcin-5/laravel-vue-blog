import type { AdminPostItem as PostItem } from '@/types/blog.types';
import { describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';
import { usePostFormLogic } from '../usePostFormLogic';

// Mock @inertiajs/vue3
vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data) => ({
        ...data,
        processing: false,
        errors: {},
    })),
}));

describe('usePostFormLogic', () => {
    it('initializes form with post data', () => {
        const post: Partial<PostItem> = {
            id: 1,
            blog_id: 10,
            title: 'Test Post',
            excerpt: 'Sample excerpt',
            content: 'Sample content',
            is_published: true,
            visibility: 'public',
        };

        const { form } = usePostFormLogic({ post: post as PostItem });

        expect(form.title).toBe('Test Post');
        expect(form.blog_id).toBe(10);
        expect(form.excerpt).toBe('Sample excerpt');
        expect(form.is_published).toBe(true);
    });

    it('initializes form with blogId when post is not provided', () => {
        const { form } = usePostFormLogic({ blogId: 5 });
        expect(form.blog_id).toBe(5);
        expect(form.title).toBe('');
    });

    it('sets correct fieldIdPrefix for create mode', () => {
        const { fieldIdPrefix } = usePostFormLogic({ isEdit: false, blogId: 10 });
        expect(fieldIdPrefix.value).toBe('create-post-10');
    });

    it('sets correct fieldIdPrefix for edit mode', () => {
        const post: Partial<PostItem> = { id: 123 };
        const { fieldIdPrefix } = usePostFormLogic({ isEdit: true, post: post as PostItem });
        expect(fieldIdPrefix.value).toBe('edit-post-123');
    });

    it('uses external form if provided', () => {
        const externalForm = { title: 'External' } as any;
        const { form } = usePostFormLogic({ externalForm });
        expect(form).toBe(externalForm);
    });

    it('watches for post changes and updates form', async () => {
        const post1: Partial<PostItem> = { id: 1, title: 'Post 1', blog_id: 1 };
        const post2: Partial<PostItem> = { id: 2, title: 'Post 2', blog_id: 1 };

        const options = reactive({ post: post1 as PostItem });
        const { form } = usePostFormLogic(options);

        expect(form.title).toBe('Post 1');

        options.post = post2 as PostItem;
        await nextTick();

        expect(form.title).toBe('Post 2');
    });

    it('watches for blogId changes in create mode', async () => {
        const options = reactive({ blogId: 1, isEdit: false });
        const { form } = usePostFormLogic(options);

        expect(form.blog_id).toBe(1);

        options.blogId = 2;
        await nextTick();

        expect(form.blog_id).toBe(2);
    });
});
