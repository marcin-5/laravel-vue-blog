import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import type { EnneagramDebugState } from '../../types';
import DebugScores from '../DebugScores.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string, values?: Record<string, string | number>) => (values ? `${key}:${JSON.stringify(values)}` : key),
    }),
}));

const debug: EnneagramDebugState = {
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
};

function mountDebugScores(stage: number, part: number) {
    return mount(DebugScores, { props: { debug, stage, part } });
}

function sectionCardHeadings(wrapper: ReturnType<typeof mountDebugScores>, title: string): string[] {
    const heading = wrapper.findAll('h3').find((element) => element.text() === title);
    const section = heading?.element.parentElement;

    return section ? Array.from(section.querySelectorAll('h4')).map((element) => element.textContent?.replace(/\s+/g, ' ').trim() ?? '') : [];
}

describe('DebugScores.vue', () => {
    it('shows only the first stage one part at the beginning of stage one', () => {
        const wrapper = mountDebugScores(1, 1);

        expect(sectionCardHeadings(wrapper, 'stage1_instincts')).toEqual(['part 1']);
        expect(wrapper.findAll('h3').map((heading) => heading.text())).not.toContain('stage2_types');
    });

    it('shows the current stage one part before the previous part', () => {
        const wrapper = mountDebugScores(1, 2);

        expect(sectionCardHeadings(wrapper, 'stage1_instincts')).toEqual(['part 2', 'part 1']);
        expect(wrapper.findAll('h3').map((heading) => heading.text())).not.toContain('stage2_types');
    });

    it('shows the total before the first stage two part without future parts', () => {
        const wrapper = mountDebugScores(2, 1);

        expect(sectionCardHeadings(wrapper, 'stage2_types')).toEqual(['total_sum', 'part 1']);
        expect(wrapper.text()).toContain('4');
    });

    it('shows later stage two parts in reverse order without future parts', () => {
        const wrapper = mountDebugScores(2, 3);

        expect(sectionCardHeadings(wrapper, 'stage2_types')).toEqual(['total_sum', 'part 3', 'part 2', 'part 1']);
    });
});
