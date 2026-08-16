import type { Blog } from '@/types/blog.types';
import type { NewsletterConfig } from '@/types/newsletter.types';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { forms, routeMock } = vi.hoisted(() => ({
    forms: [] as Array<Record<string, unknown>>,
    routeMock: vi.fn((name: string) => `/${name}`),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((data: Record<string, unknown>) => {
        const form = {
            ...data,
            processing: false,
            errors: {},
            transform: vi.fn((callback: (value: Record<string, unknown>) => unknown) => {
                (form as Record<string, unknown>).transformed = callback(form);
                return form;
            }),
            post: vi.fn(),
        };

        forms.push(form);
        return form;
    }),
}));

import { useNewsletterForm } from '../useNewsletterForm';

const config: NewsletterConfig = {
    daily_weekday_time: '08:00',
    daily_weekend_time: '09:00',
    weekly_day: 1,
    weekly_time: '10:00',
};

const blog = (id: number): Blog => ({
    id,
    name: `Blog ${id}`,
    slug: `blog-${id}`,
    url: `/blog-${id}`,
});

const translate = (key: string): string => key;

describe('useNewsletterForm', () => {
    beforeEach(() => {
        forms.length = 0;
        routeMock.mockClear();
        vi.stubGlobal('route', routeMock);
    });

    it('creates defaults from the selected blog and newsletter config', () => {
        const { newsletterForm } = useNewsletterForm({
            mode: 'subscribe',
            blogs: [blog(1), blog(2)],
            config,
            selectedBlogId: 2,
            t: translate,
        });

        expect(newsletterForm.subscriptions).toEqual([
            {
                blog_id: 1,
                selected: false,
                frequency: 'weekly',
                send_time: '10:00',
                send_time_weekend: null,
                send_day: 1,
            },
            {
                blog_id: 2,
                selected: true,
                frequency: 'weekly',
                send_time: '10:00',
                send_time_weekend: null,
                send_day: 1,
            },
        ]);
    });

    it('transforms the form to selected subscription payloads without changing its state', () => {
        const { newsletterForm, submit } = useNewsletterForm({
            mode: 'subscribe',
            blogs: [blog(1), blog(2)],
            config,
            t: translate,
        });
        newsletterForm.subscriptions[0].selected = true;
        newsletterForm.subscriptions[1].selected = false;

        submit();

        expect(newsletterForm.transform).toHaveBeenCalledOnce();
        expect(forms[0].transformed).toEqual({
            email: '',
            subscriptions: [
                {
                    blog_id: 1,
                    frequency: 'weekly',
                    send_time: '10:00',
                    send_time_weekend: null,
                    send_day: 1,
                },
            ],
        });
        expect(newsletterForm.subscriptions).toHaveLength(2);
        expect(newsletterForm.post).toHaveBeenCalledWith('/newsletter.store', expect.any(Object));
    });

    it('does not submit subscribe mode without selected blogs', () => {
        const { newsletterForm, submit } = useNewsletterForm({
            mode: 'subscribe',
            blogs: [blog(1)],
            config,
            t: translate,
        });

        submit();

        expect(newsletterForm.post).not.toHaveBeenCalled();
    });
});
