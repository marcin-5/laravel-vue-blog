import BloggerListItem from '@/components/blogger/BloggerListItem.vue';
import GroupForm from '@/components/blogger/GroupForm.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import type { AdminGroup, BloggerItemContext } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import GroupListItem from '../GroupListItem.vue';

// Mock dependencies
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
        locale: { value: 'en' },
    }),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data) => ({
        ...data,
        errors: {},
        processing: false,
        reset: vi.fn(),
        post: vi.fn(),
        patch: vi.fn(),
    })),
    usePage: () => ({
        props: {
            auth: {
                user: { id: 1 },
            },
        },
    }),
    router: {
        visit: vi.fn(),
        reload: vi.fn(),
    },
}));

// Mock components
vi.mock('@/components/blogger/BloggerListItem.vue', async () => {
    const { defineComponent, h } = await import('vue');

    return {
        default: defineComponent({
            name: 'BloggerListItem',
            props: ['context'],
            setup(props, { slots }) {
                return () =>
                    h('div', [
                        slots.actions?.({
                            handleEdit: props.context.actions.edit,
                            handleCreatePost: props.context.actions.createPost,
                            handleTogglePosts: props.context.actions.togglePosts,
                            isCreatingPost: props.context.isCreatingPost,
                            isEditing: props.context.isEditing,
                            isPostsExpanded: props.context.isPostsExpanded,
                        }),
                        ...(props.context.isEditing ? [slots['edit-form']?.({ handleCancelEdit: props.context.actions.cancelEdit })] : []),
                        ...(props.context.isCreatingPost
                            ? [slots['create-post-form']?.({ handleCancelCreatePost: props.context.actions.cancelCreatePost })]
                            : []),
                    ]);
            },
        }),
    };
});
vi.mock('@/components/blogger/GroupForm.vue', () => ({
    default: { name: 'GroupForm', template: '<div>GroupForm</div>', props: ['form', 'group', 'isEdit', 'idPrefix'] },
}));
vi.mock('@/components/blogger/ItemActionGroup.vue', () => ({
    default: { name: 'ItemActionGroup', template: '<div><slot name="prefix" />ItemActionGroup</div>' },
}));
vi.mock('@/components/blogger/PostForm.vue', () => ({
    default: { name: 'PostForm', template: '<div>PostForm</div>', props: ['form', 'groupId'] },
}));
vi.mock('@/components/ui/badge', () => ({
    Badge: { name: 'Badge', template: '<div><slot /></div>' },
}));
vi.mock('@/components/ui/tooltip', () => ({
    TooltipButton: { name: 'TooltipButton', template: '<div><slot /></div>' },
}));

// Mock route function
(global as any).route = vi.fn(() => 'mock-route');

describe('GroupListItem.vue', () => {
    const mockGroup: AdminGroup = {
        id: 1,
        user_id: 1,
        name: 'Test Group',
        slug: 'test-group',
        content: 'Test Content',
        footer: 'Test Footer',
        is_published: true,
        allow_registration: false,
        locale: 'en',
    };

    const defaultProps = { item: mockGroup };

    it('renders BloggerListItem with correct props', () => {
        const wrapper = mount(GroupListItem, {
            props: defaultProps,
        });

        const bloggerListItem = wrapper.findComponent(BloggerListItem);
        expect(bloggerListItem.exists()).toBe(true);
        const { context } = bloggerListItem.props() as { context: BloggerItemContext<AdminGroup> };
        expect(context.subtitle).toBe(mockGroup.slug);
    });

    it('renders GroupForm in edit-form slot', async () => {
        const wrapper = mount(GroupListItem, { props: defaultProps });
        const { context } = wrapper.findComponent(BloggerListItem).props() as { context: BloggerItemContext<AdminGroup> };
        context.actions.edit();
        await nextTick();

        const groupForm = wrapper.findComponent(GroupForm);
        expect(groupForm.exists()).toBe(true);
        expect(groupForm.props('isEdit')).toBeDefined();
        expect(groupForm.props('group')).toEqual(mockGroup);
    });

    it('renders PostForm in create-post-form slot', async () => {
        const wrapper = mount(GroupListItem, { props: defaultProps });
        const { context } = wrapper.findComponent(BloggerListItem).props() as { context: BloggerItemContext<AdminGroup> };
        context.actions.createPost();
        await nextTick();

        const postForm = wrapper.findComponent(PostForm);
        expect(postForm.exists()).toBe(true);
        expect(postForm.props('groupId')).toBe(mockGroup.id);
    });

    it('submits the local entity form when GroupForm emits submit', async () => {
        const wrapper = mount(GroupListItem, { props: defaultProps });
        const { context } = wrapper.findComponent(BloggerListItem).props() as { context: BloggerItemContext<AdminGroup> };
        context.actions.edit();
        await nextTick();

        const groupForm = wrapper.findComponent(GroupForm);
        const mockForm = { name: 'Updated Group' };
        groupForm.vm.$emit('submit', mockForm);

        expect(groupForm.exists()).toBe(true);
    });
});
