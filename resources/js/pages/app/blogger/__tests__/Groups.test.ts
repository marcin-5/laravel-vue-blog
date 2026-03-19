import CreateEntitySection from '@/components/blogger/CreateEntitySection.vue';
import GroupListItem from '@/components/blogger/GroupListItem.vue';
import type { AdminGroup } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Groups from '../Groups.vue';

// Mock dependencies
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));
vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn(() => ({
        processing: false,
        errors: {},
        reset: vi.fn(),
    })),
    Head: { name: 'Head', template: '<div><slot /></div>' },
    router: {
        reload: vi.fn(),
    },
}));

// Mock components
vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { name: 'AppLayout', template: '<div><slot /></div>' },
}));
vi.mock('@/components/blogger/CreateEntitySection.vue', () => ({
    default: {
        name: 'CreateEntitySection',
        template: '<div><slot name="form" :form="{}" :onCancel="() => {}" :onSubmit="() => {}" /></div>',
    },
}));
vi.mock('@/components/blogger/GroupForm.vue', () => ({
    default: { name: 'GroupForm', template: '<div>GroupForm</div>' },
}));
vi.mock('@/components/blogger/GroupListItem.vue', () => ({
    default: { name: 'GroupListItem', template: '<div>GroupListItem</div>' },
}));
vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', template: '<button><slot /></button>' },
}));

// Mock composables
vi.mock('@/composables/useGroupForm', () => ({
    useGroupForm: () => ({
        showCreate: { value: false },
        editingId: { value: null },
        createForm: {},
        editForm: {},
        openCreateForm: vi.fn(),
        closeCreateForm: vi.fn(),
        submitCreate: vi.fn(),
        startEdit: vi.fn(),
        cancelEdit: vi.fn(),
        submitEdit: vi.fn(),
    }),
}));

vi.mock('@/composables/usePostForm', () => ({
    usePostForm: () => ({
        creatingPostForId: { value: null },
        editingPostId: { value: null },
        postForm: { reset: vi.fn() },
        postEditForm: { reset: vi.fn() },
        startCreatePostInGroup: vi.fn(),
        cancelCreatePost: vi.fn(),
        submitCreatePost: vi.fn(),
        startEditPost: vi.fn(),
        cancelEditPost: vi.fn(),
        submitEditPost: vi.fn(),
    }),
}));

vi.mock('@/composables/useUIState', () => ({
    useUIState: () => ({
        expandedPostsForId: { value: null },
        expandedExtensionsForId: { value: null },
        togglePosts: vi.fn(),
        toggleExtensions: vi.fn(),
    }),
}));

describe('Groups.vue', () => {
    const mockGroups: AdminGroup[] = [
        { id: 1, name: 'Group 1', slug: 'group-1', user_id: 1, content: null, footer: null, is_published: true, locale: 'en' },
        { id: 2, name: 'Group 2', slug: 'group-2', user_id: 1, content: null, footer: null, is_published: true, locale: 'en' },
    ];

    const defaultProps = {
        groups: mockGroups,
        canCreate: true,
    };

    it('renders the groups list', () => {
        const wrapper = mount(Groups, {
            props: defaultProps,
            global: {
                stubs: {
                    Head: true,
                },
                mocks: {
                    $t: (key: string) => key,
                },
            },
        });

        const groupItems = wrapper.findAllComponents(GroupListItem);
        expect(groupItems.length).toBe(mockGroups.length);
    });

    it('renders empty state when no groups are provided', () => {
        const wrapper = mount(Groups, {
            props: {
                groups: [],
                canCreate: true,
            },
            global: {
                stubs: {
                    Head: true,
                },
                mocks: {
                    $t: (key: string) => key,
                },
            },
        });

        expect(wrapper.text()).toContain('blogger.groups.empty');
        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('renders CreateEntitySection', () => {
        const wrapper = mount(Groups, {
            props: defaultProps,
            global: {
                stubs: {
                    Head: true,
                },
                mocks: {
                    $t: (key: string) => key,
                },
            },
        });

        const createSection = wrapper.findComponent(CreateEntitySection as any);
        expect(createSection.exists()).toBe(true);
    });
});
