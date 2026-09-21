import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import BloggerListItem from '../BloggerListItem.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

describe('BloggerListItem.vue', () => {
    it('passes the post edit form to each post item', () => {
        const postEditForm = { title: 'Post edit form' };
        const post = {
            id: 7,
            blog_id: 0,
            title: 'Group post',
            slug: 'group-post',
            excerpt: null,
            is_published: true,
        };
        const context = {
            item: {
                id: 2,
                name: 'Group',
                slug: 'group',
                is_published: true,
                posts: [post],
            },
            isEditing: false,
            isCreatingPost: false,
            isPostsExpanded: true,
            editingPostId: post.id,
            expandedExtensionsForId: null,
            editForm: { name: 'Group edit form' },
            postEditForm,
            actions: {
                edit: vi.fn(),
                createPost: vi.fn(),
                togglePosts: vi.fn(),
                editPost: vi.fn(),
                toggleExtensions: vi.fn(),
                cancelEdit: vi.fn(),
                cancelCreatePost: vi.fn(),
                submitEdit: vi.fn(),
                submitCreatePost: vi.fn(),
                cancelEditPost: vi.fn(),
                submitEditPost: vi.fn(),
                createExtension: vi.fn(),
                submitCreateExtension: vi.fn(),
                cancelCreateExtension: vi.fn(),
                editExtension: vi.fn(),
                submitEditExtension: vi.fn(),
                cancelEditExtension: vi.fn(),
            },
        };

        const postListItemStub = {
            name: 'PostListItem',
            props: ['editForm', 'isEditing', 'isExtensionsExpanded', 'post'],
            template: '<div />',
        };

        const wrapper = mount(BloggerListItem, {
            props: { context },
            global: {
                stubs: {
                    BaseListItem: {
                        template: '<div><slot name="posts-list" /><slot name="actions" /></div>',
                    },
                    PostListItem: postListItemStub,
                },
            },
        });

        expect(wrapper.findComponent(postListItemStub).props('editForm')).toEqual(postEditForm);
    });
});