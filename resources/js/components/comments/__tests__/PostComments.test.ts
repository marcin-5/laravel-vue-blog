import type { Thread } from '@/types/blog.types';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import PostComments from '../PostComments.vue';

const { post, usePage } = vi.hoisted(() => ({
    post: vi.fn(),
    usePage: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Link: defineComponent({
        setup(_, { slots }) {
            return () => h('a', slots.default?.());
        },
    }),
    useHttp: () => ({
        title: '',
        visibility: 'public',
        content: '',
        parent_id: null,
        post,
    }),
    usePage,
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string, fallback?: string) => fallback ?? key }),
}));

vi.mock('../ThreadItem.vue', () => ({
    default: defineComponent({
        name: 'ThreadItem',
        props: ['thread', 'expanded', 'locked', 'comments', 'loading', 'error', 'isAuthenticated', 'canReply', 'commentsMaxDepth'],
        emits: ['toggle', 'toggle-lock', 'reply', 'thread-reply'],
        template: '<button class="thread-item" @click="$emit(\'toggle\')">{{ thread.title }}</button>',
    }),
}));

vi.mock('../CreateThreadDialog.vue', () => ({
    default: defineComponent({
        name: 'CreateThreadDialog',
        props: ['open', 'isGroup', 'processing', 'error'],
        emits: ['update:open', 'submit'],
        template: '<div class="create-thread-dialog" />',
    }),
}));

const threads: Thread[] = [
    {
        id: 1,
        post_id: 10,
        user_id: 2,
        title: 'First topic',
        visibility: 'public',
        is_locked: false,
        comments_count: 2,
    },
    {
        id: 2,
        post_id: 10,
        user_id: 3,
        title: 'Second topic',
        visibility: 'public',
        is_locked: false,
        comments_count: 1,
    },
];

function mountComments() {
    return mount(PostComments, {
        props: {
            postId: 10,
            threads,
        },
    });
}

describe('PostComments.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        usePage.mockReturnValue({ props: { auth: { user: { id: 1 } } } });
        (globalThis as { route?: (name: string, params?: Record<string, number>) => string }).route = (name, params) =>
            `/${name}/${Object.values(params ?? {})[0] ?? ''}`;
        vi.stubGlobal(
            'fetch',
            vi.fn().mockResolvedValue({
                ok: true,
                json: async () => [],
            }),
        );
    });

    it('renders all thread subjects collapsed by default', () => {
        const wrapper = mountComments();
        const items = wrapper.findAllComponents({ name: 'ThreadItem' });

        expect(items).toHaveLength(2);
        expect(items[0].props('expanded')).toBe(false);
        expect(items[1].props('expanded')).toBe(false);
    });

    it('loads comments on expansion and collapses unlocked threads', async () => {
        const wrapper = mountComments();
        const items = wrapper.findAllComponents({ name: 'ThreadItem' });

        await items[0].vm.$emit('toggle');
        await flushPromises();
        expect(globalThis.fetch).toHaveBeenCalledTimes(1);
        expect(wrapper.findAllComponents({ name: 'ThreadItem' })[0].props('expanded')).toBe(true);

        await wrapper.findAllComponents({ name: 'ThreadItem' })[1].vm.$emit('toggle');
        expect(wrapper.findAllComponents({ name: 'ThreadItem' })[0].props('expanded')).toBe(false);
        expect(wrapper.findAllComponents({ name: 'ThreadItem' })[1].props('expanded')).toBe(true);
    });

    it('keeps a locked expanded thread open when another thread expands', async () => {
        const wrapper = mountComments();
        let items = wrapper.findAllComponents({ name: 'ThreadItem' });

        await items[0].vm.$emit('toggle');
        await items[0].vm.$emit('toggle-lock');
        await items[1].vm.$emit('toggle');

        items = wrapper.findAllComponents({ name: 'ThreadItem' });
        expect(items[0].props('expanded')).toBe(true);
        expect(items[0].props('locked')).toBe(true);
        expect(items[1].props('expanded')).toBe(true);
    });
});
