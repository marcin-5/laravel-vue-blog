import BloggerListItem from '@/components/blogger/BloggerListItem.vue';
import GroupForm from '@/components/blogger/GroupForm.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import type { AdminGroup } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import GroupListItem from '../GroupListItem.vue';

// Mock dependencies
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            auth: {
                user: { id: 1 },
            },
        },
    }),
    router: {
        visit: vi.fn(),
    },
}));

// Mock components
vi.mock('@/components/blogger/BloggerListItem.vue', () => ({
    default: {
        name: 'BloggerListItem',
        template:
            '<div><slot name="actions" :handleEdit="() => {}" :handleCreatePost="() => {}" :handleTogglePosts="() => {}" :isCreatingPost="isCreatingPost" :isEditing="isEditing" :isPostsExpanded="isPostsExpanded" /><slot v-if="isEditing" name="edit-form" :handleCancelEdit="() => {}" /><slot v-if="isCreatingPost" name="create-post-form" :handleCancelCreatePost="() => {}" /></div>',
        props: ['subtitle', 'isEditing', 'isCreatingPost', 'isPostsExpanded'],
    },
}));
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
        locale: 'en',
    };

    const defaultProps = {
        item: mockGroup,
        isEditing: false,
        isCreatingPost: false,
        isPostsExpanded: false,
        editForm: {} as any,
        postForm: {} as any,
        postEditForm: {} as any,
    };

    it('renders BloggerListItem with correct props', () => {
        const wrapper = mount(GroupListItem, {
            props: defaultProps,
        });

        const bloggerListItem = wrapper.findComponent(BloggerListItem as any);
        expect(bloggerListItem.exists()).toBe(true);
        expect(bloggerListItem.props('subtitle')).toBe(mockGroup.slug);
    });

    it('renders GroupForm in edit-form slot', () => {
        const wrapper = mount(GroupListItem, {
            props: {
                ...defaultProps,
                isEditing: true,
            },
        });

        const groupForm = wrapper.findComponent(GroupForm as any);
        expect(groupForm.exists()).toBe(true);
        expect(groupForm.props('isEdit')).toBeDefined();
        expect(groupForm.props('group')).toEqual(mockGroup);
    });

    it('renders PostForm in create-post-form slot', () => {
        const wrapper = mount(GroupListItem, {
            props: {
                ...defaultProps,
                isCreatingPost: true,
            },
        });

        const postForm = wrapper.findComponent(PostForm as any);
        expect(postForm.exists()).toBe(true);
        expect(postForm.props('groupId')).toBe(mockGroup.id);
    });

    it('emits submitEdit when GroupForm emits submit', async () => {
        const wrapper = mount(GroupListItem, {
            props: {
                ...defaultProps,
                isEditing: true,
            },
        });

        const groupForm = wrapper.findComponent(GroupForm as any);
        const mockForm = { name: 'Updated Group' };
        await groupForm.vm.$emit('submit', mockForm);

        expect(wrapper.emitted('submitEdit')).toBeTruthy();
        expect(wrapper.emitted('submitEdit')![0]).toEqual([mockForm, mockGroup]);
    });
});
