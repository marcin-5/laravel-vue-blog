import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import DebugScores from '../DebugScores.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string, values?: Record<string, string | number>) => (values ? `${key}:${JSON.stringify(values)}` : key),
    }),
}));

describe('DebugScores.vue', () => {
    it('renders live scores for every stage part and the stage two total', () => {
        const wrapper = mount(DebugScores, {
            props: {
                debug: {
                    stage1: {
                        part1: { sp: 2, so: 1, sx: 0 },
                        part2: { sp: 0, so: 3, sx: 1 },
                    },
                    stage2: {
                        total: { '1': 4, '2': 2 },
                        perPart: {
                            '1': { '1': 2, '2': 1 },
                            '2': { '1': 1, '2': 0 },
                            '3': { '1': 1, '2': 1 },
                            '4': { '1': 0, '2': 0 },
                        },
                    },
                },
            },
        });

        expect(wrapper.text()).toContain('2');
        expect(wrapper.text()).toContain('3');
        expect(wrapper.text()).toContain('4');
        expect(wrapper.text()).toContain('part 1');
        expect(wrapper.text()).toContain('part 4');
        expect(wrapper.text()).toContain('total_sum');
    });
});
