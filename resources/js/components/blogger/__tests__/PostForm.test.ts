import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PostForm from '../PostForm.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

const mockForm = {
    blog_id: 0,
    group_id: 0,
    title: '',
    excerpt: '',
    content: '',
    is_published: false,
    visibility: 'public',
    processing: false,
    errors: {},
    patch: vi.fn(),
};

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data) => ({
        ...data,
        processing: false,
        errors: {},
        patch: vi.fn(),
    })),
}));

vi.mock('@vueuse/core', () => ({
    useDebounceFn: vi.fn((fn) => fn),
}));

vi.mock('@/composables/useMarkdownPreview', () => ({
    useMarkdownPreview: vi.fn(() => ({
        isPreviewMode: { value: false },
        isFullPreview: { value: false },
        previewLayout: { value: 'vertical' },
        previewHtml: { value: '' },
        renderMarkdown: vi.fn(),
        togglePreview: vi.fn(),
        toggleFullPreview: vi.fn(),
        setLayout: vi.fn(),
    })),
}));

vi.mock('@/components/blogger/FormCheckboxField.vue', () => ({
    default: { name: 'FormCheckboxField', template: '<div>FormCheckboxField</div>' },
}));
vi.mock('@/components/blogger/FormSubmitActions.vue', () => ({
    default: { name: 'FormSubmitActions', template: '<div>FormSubmitActions</div>' },
}));
vi.mock('@/components/blogger/MarkdownPreviewSection.vue', () => ({
    default: { name: 'MarkdownPreviewSection', template: '<div>MarkdownPreviewSection</div>' },
}));
vi.mock('@/components/blogger/PostFormField.vue', () => ({
    default: {
        name: 'PostFormField',
        props: ['id', 'label', 'error', 'placeholder', 'type', 'rows', 'required', 'tooltip', 'inputClass', 'hint'],
        template: '<div :id="id">PostFormField</div>',
    },
}));
vi.mock('@/components/blogger/PostRelatedPostsSection.vue', () => ({
    default: {
        name: 'PostRelatedPostsSection',
        props: ['translations'],
        template: '<div><h3>{{ translations.label }}</h3>PostRelatedPostsSection</div>',
    },
}));
vi.mock('@/components/blogger/PostExternalLinksSection.vue', () => ({
    default: {
        name: 'PostExternalLinksSection',
        props: ['translations'],
        template: '<div><h3>{{ translations.label }}</h3>PostExternalLinksSection</div>',
    },
}));

(global as any).route = vi.fn(() => 'mock-route');

describe('PostForm.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        (global as any).route = vi.fn(() => 'mock-route');
    });

    it('renders the form', () => {
        const wrapper = mount(PostForm);
        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('renders related posts section', () => {
        const wrapper = mount(PostForm);
        const section = wrapper.findComponent({ name: 'PostRelatedPostsSection' });
        expect(section.exists()).toBe(true);
        expect(section.find('h3').text()).toBe('blogger.post_form.related_posts_label');
    });

    it('renders external links section', () => {
        const wrapper = mount(PostForm);
        const section = wrapper.findComponent({ name: 'PostExternalLinksSection' });
        expect(section.exists()).toBe(true);
        expect(section.find('h3').text()).toBe('blogger.post_form.external_links_label');
    });

    it('renders seo title field only when NOT in group', () => {
        const wrapper = mount(PostForm, {
            props: {
                groupId: 0,
            },
        });
        const seoTitleField = wrapper.findAllComponents({ name: 'PostFormField' }).find((c) => c.props('id') === 'post-seo-title');
        expect(seoTitleField).toBeDefined();

        const groupWrapper = mount(PostForm, {
            props: {
                groupId: 1,
            },
        });
        const hiddenSeoTitleField = groupWrapper.findAllComponents({ name: 'PostFormField' }).find((c) => c.props('id') === 'post-seo-title');
        expect(hiddenSeoTitleField).toBeUndefined();
    });

    it('emits submit when form is submitted', async () => {
        const wrapper = mount(PostForm);

        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')).toBeTruthy();
        expect(wrapper.emitted('submit')![0][0]).toBeDefined();
    });

    it('emits cancel when handleCancel is called', async () => {
        const wrapper = mount(PostForm);

        const submitActions = wrapper.findComponent({ name: 'FormSubmitActions' });
        await submitActions.vm.$emit('cancel');

        expect(wrapper.emitted('cancel')).toBeTruthy();
    });

    it('initializes form with post data when post prop is provided', () => {
        const post = {
            id: 1,
            blog_id: 2,
            group_id: 3,
            title: 'Test Post',
            excerpt: 'Test excerpt',
            content: '# Content',
            is_published: true,
            visibility: 'public',
        };

        const wrapper = mount(PostForm, {
            props: { post: post as any },
        });

        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('uses external form when form prop is provided', () => {
        const wrapper = mount(PostForm, {
            props: { form: mockForm as any },
        });

        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('calls form.patch when handleApply is called in edit mode', async () => {
        const patchMock = vi.fn();
        const post = {
            id: 5,
            blog_id: 1,
            group_id: 0,
            title: 'Post',
            excerpt: '',
            content: '',
            is_published: false,
            visibility: 'public',
        };

        const wrapper = mount(PostForm, {
            props: {
                isEdit: true,
                post: post as any,
                form: { ...mockForm, patch: patchMock } as any,
            },
        });

        const submitActions = wrapper.findComponent({ name: 'FormSubmitActions' });
        await submitActions.vm.$emit('apply');

        expect(patchMock).toHaveBeenCalled();
        expect((global as any).route).toHaveBeenCalledWith('posts.update', 5);
    });

    it('renders with isEdit prop set to true', () => {
        const post = {
            id: 1,
            blog_id: 1,
            group_id: 0,
            title: 'Edit Post',
            excerpt: '',
            content: '',
            is_published: false,
            visibility: 'public',
        };

        const wrapper = mount(PostForm, {
            props: { isEdit: true, post: post as any },
        });

        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('calls togglePreview when MarkdownPreviewSection emits toggle-preview', async () => {
        const { useMarkdownPreview } = await import('@/composables/useMarkdownPreview');
        const togglePreviewMock = vi.fn();
        vi.mocked(useMarkdownPreview).mockReturnValueOnce({
            isPreviewMode: { value: false } as any,
            isFullPreview: { value: false } as any,
            previewLayout: { value: 'vertical' } as any,
            previewHtml: { value: '' } as any,
            renderMarkdown: vi.fn(),
            togglePreview: togglePreviewMock,
            toggleFullPreview: vi.fn(),
            setLayout: vi.fn(),
        });

        const wrapper = mount(PostForm);
        const markdownSection = wrapper.findComponent({ name: 'MarkdownPreviewSection' });
        await markdownSection.vm.$emit('toggle-preview');

        expect(togglePreviewMock).toHaveBeenCalled();
    });

    it('calls toggleFullPreview when MarkdownPreviewSection emits toggle-full-preview', async () => {
        const { useMarkdownPreview } = await import('@/composables/useMarkdownPreview');
        const toggleFullPreviewMock = vi.fn();
        vi.mocked(useMarkdownPreview).mockReturnValueOnce({
            isPreviewMode: { value: false } as any,
            isFullPreview: { value: false } as any,
            previewLayout: { value: 'vertical' } as any,
            previewHtml: { value: '' } as any,
            renderMarkdown: vi.fn(),
            togglePreview: vi.fn(),
            toggleFullPreview: toggleFullPreviewMock,
            setLayout: vi.fn(),
        });

        const wrapper = mount(PostForm);
        const markdownSection = wrapper.findComponent({ name: 'MarkdownPreviewSection' });
        await markdownSection.vm.$emit('toggle-full-preview');

        expect(toggleFullPreviewMock).toHaveBeenCalled();
    });
});
