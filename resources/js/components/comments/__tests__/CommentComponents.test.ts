import CommentItem from '@/components/comments/CommentItem.vue';
import CommentReplyForm from '@/components/comments/CommentReplyForm.vue';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', async () => {
    const { defineComponent, h } = await import('vue');

    return {
        Link: defineComponent({
            setup(_, { slots }) {
                return () => h('a', slots.default?.());
            },
        }),
    };
});

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string, fallback?: string) => fallback ?? key }),
}));

const comment = {
    id: 1,
    thread_id: 10,
    user_id: 2,
    parent_id: null,
    content: 'Root comment',
    depth: 1,
    children: [
        {
            id: 2,
            thread_id: 10,
            user_id: 3,
            parent_id: 1,
            content: 'First reply',
            depth: 2,
            children: [
                {
                    id: 3,
                    thread_id: 10,
                    user_id: 4,
                    parent_id: 2,
                    content: 'Second reply',
                    depth: 3,
                    children: [],
                },
            ],
        },
    ],
};

describe('comment components', () => {
    it('uses a comment placeholder for thread-level comments and a reply placeholder for nested replies', async () => {
        const wrapper = mount(CommentReplyForm, {
            props: {
                isAuthenticated: true,
                isThreadLevel: true,
            },
        });

        expect(wrapper.get('textarea').attributes('placeholder')).toBe('Write a comment...');

        await wrapper.setProps({ isThreadLevel: false });
        await nextTick();

        expect(wrapper.get('textarea').attributes('placeholder')).toBe('Write a reply...');
    });

    it('uses a fixed indentation step for nested comment levels', () => {
        const wrapper = mount(CommentItem, {
            props: {
                canReply: false,
                comment,
                isAuthenticated: true,
            },
            global: {
                stubs: {
                    CommentReplyForm: true,
                },
            },
        });
        const articles = wrapper.findAll('article');

        expect(articles[0].element.style.paddingInlineStart).toBe('0rem');
        expect(articles[1].element.style.paddingInlineStart).toBe('0.75rem');
        expect(articles[2].element.style.paddingInlineStart).toBe('1.5rem');
    });
});
