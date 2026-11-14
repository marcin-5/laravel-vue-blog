import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import FontUsageStats from '../FontUsageStats.vue';
import { router } from '@inertiajs/vue3';

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

vi.mock('lucide-vue-next', () => ({
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
    });

    it('filters by blogger through the dashboard query string', async () => {
        const wrapper = mount(FontUsageStats, { props: { stats } });

        await wrapper.findAll('button')[1].trigger('click');

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
        expect(wrapper.find('.grid').classes()).toEqual(expect.arrayContaining(['grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-3']));
        expect(wrapper.findAll('.grid > div')).toHaveLength(2);
        expect(wrapper.find('.grid > div:nth-child(2) div > span').classes()).toContain('text-warning');
    });

    it('keeps the default order and can sort fonts by usage count', async () => {
        const wrapper = mount(FontUsageStats, { props: { stats } });

        expect(wrapper.findAll('.grid > div > span').map((font) => font.text())).toEqual(['Inter', 'Afacad']);

        await wrapper.find('select').setValue('true');

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

        await wrapper.find('select').setValue('true');

        expect(wrapper.findAll('.grid > div > span').map((font) => font.text())).toEqual(['Inter', 'Afacad']);
        expect(wrapper.props('stats').fonts.map((font) => font.label)).toEqual(['Afacad', 'Inter']);
    });
});