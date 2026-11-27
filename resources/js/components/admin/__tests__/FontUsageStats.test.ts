import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import FontUsageStats from '../FontUsageStats.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: {
        get: vi.fn(),
    },
}));

vi.mock('@/components/stats/FilterSelect.vue', () => ({
    default: {
        name: 'FilterSelect',
        props: ['modelValue', 'options'],
        emits: ['update:modelValue'],
        template: `<div><button v-for="option in options" :key="option.value" type="button" @click="$emit('update:modelValue', option.value === 'all' ? undefined : option.value)">{{ option.label }}</button></div>`,
    },
}));

vi.mock('@/components/ui/card', () => ({
    Card: { template: '<div><slot /></div>' },
    CardContent: { template: '<div><slot /></div>' },
    CardHeader: { template: '<div><slot /></div>' },
    CardTitle: { template: '<div><slot /></div>' },
}));

vi.mock('@/components/ui/tooltip', () => ({
    Tooltip: { template: '<div><slot /></div>' },
    TooltipContent: { template: '<div><slot /></div>' },
    TooltipProvider: { template: '<div><slot /></div>' },
    TooltipTrigger: { template: '<div><slot /></div>' },
}));

vi.mock('@lucide/vue', () => ({
    ChevronDown: { template: '<span />' },
    ChevronUp: { template: '<span />' },
    Info: { template: '<span />' },
}));

(global as any).route = vi.fn(() => '/dashboard');

const stats = {
    bloggers: [{ id: 7, name: 'Blogger Seven' }],
    selectedBloggerId: null,
    fonts: [
        { value: 'var(--font-inter)', label: 'Inter', count: 1, blogs: ['Alpha blog'] },
        { value: 'var(--font-afacad)', label: 'Afacad', count: 0, blogs: [] },
    ],
};

describe('FontUsageStats.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();
    });

    it('filters by blogger through the dashboard query string', async () => {
        const wrapper = mount(FontUsageStats, { props: { stats } });

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Blogger Seven')!
            .trigger('click');

        expect(router.get).toHaveBeenCalledWith(
            '/dashboard',
            { blogger_id: 7 },
            { only: ['fontUsageStats'], preserveScroll: true, preserveState: true, replace: true },
        );
    });

    it('shows zero usage distinctly and lists blogs in the font tooltip', () => {
        const wrapper = mount(FontUsageStats, { props: { stats } });

        expect(wrapper.text()).toContain('Alpha blog');
        expect(wrapper.text()).toContain('admin.stats.font_usage.unused');
        expect(wrapper.find('.grid > div > span').attributes('style')).toBe('font-family: var(--font-inter); font-size: 1rem;');
        expect(wrapper.find('.grid').classes()).toEqual(expect.arrayContaining(['grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-3']));
        expect(wrapper.findAll('.grid > div')).toHaveLength(2);
        expect(wrapper.find('.grid > div:nth-child(2) div > span').classes()).toContain('text-warning');
    });

    it('applies configured font size and weight corrections to font samples', () => {
        const wrapper = mount(FontUsageStats, {
            props: {
                stats: {
                    ...stats,
                    fonts: [
                        { value: 'var(--font-kreon)', label: 'Kreon', count: 1, blogs: [] },
                        { value: 'var(--font-literata)', label: 'Literata', count: 1, blogs: [] },
                    ],
                },
            },
        });

        const [kreon, literata] = wrapper.findAll('.grid > div > span');

        expect(kreon.attributes('style')).toBe('font-family: var(--font-kreon); font-size: 1.1rem;');
        expect(literata.attributes('style')).toBe('font-family: var(--font-literata); font-size: 1rem; font-weight: 300;');
    });

    it('collapses to the title header and expands to show all font rows', async () => {
        const wrapper = mount(FontUsageStats, { props: { stats } });
        const toggle = wrapper.find('button[aria-label="Collapse font usage stats"]');

        expect(toggle.attributes('aria-expanded')).toBe('true');
        await toggle.trigger('click');

        expect(wrapper.find('button[aria-label="Expand font usage stats"]').attributes('aria-expanded')).toBe('false');
        expect(wrapper.find('.grid').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('admin.stats.font_usage.sort_label');

        await wrapper.find('button[aria-label="Expand font usage stats"]').trigger('click');

        expect(wrapper.find('button[aria-label="Collapse font usage stats"]').attributes('aria-expanded')).toBe('true');
        expect(wrapper.findAll('.grid > div')).toHaveLength(2);
    });

    it('remembers the collapsed state after remounting', async () => {
        const wrapper = mount(FontUsageStats, { props: { stats } });

        await wrapper.find('button[aria-label="Collapse font usage stats"]').trigger('click');
        expect(localStorage.getItem('font-usage-stats-collapsed')).toBe('true');
        wrapper.unmount();

        const remountedWrapper = mount(FontUsageStats, { props: { stats } });
        await nextTick();

        expect(remountedWrapper.find('button[aria-label="Expand font usage stats"]').exists()).toBe(true);
        expect(remountedWrapper.find('.grid').exists()).toBe(false);
    });

    it('keeps the default order and can sort fonts by usage count', async () => {
        const wrapper = mount(FontUsageStats, { props: { stats } });

        expect(wrapper.findAll('.grid > div > span').map((font) => font.text())).toEqual(['Inter', 'Afacad']);

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'admin.stats.font_usage.sort_usage')!
            .trigger('click');

        expect(wrapper.findAll('.grid > div > span').map((font) => font.text())).toEqual(['Inter', 'Afacad']);
    });

    it('sorts by usage count without mutating the original font list', async () => {
        const wrapper = mount(FontUsageStats, {
            props: {
                stats: {
                    ...stats,
                    fonts: [
                        { value: 'var(--font-afacad)', label: 'Afacad', count: 0, blogs: [] },
                        { value: 'var(--font-inter)', label: 'Inter', count: 2, blogs: ['Alpha blog', 'Beta blog'] },
                    ],
                },
            },
        });

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'admin.stats.font_usage.sort_usage')!
            .trigger('click');

        expect(wrapper.findAll('.grid > div > span').map((font) => font.text())).toEqual(['Inter', 'Afacad']);
        expect(wrapper.props('stats').fonts.map((font) => font.label)).toEqual(['Afacad', 'Inter']);
    });
});
