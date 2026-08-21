import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import type { TestMapStage } from '../../types';
import TestMap from '../TestMap.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

const testMap: TestMapStage[] = [
    {
        stage: 1,
        parts: [
            { part: 1, status: 'completed' },
            { part: 2, status: 'active' },
        ],
    },
    {
        stage: 2,
        parts: [{ part: 1, status: 'pending' }],
    },
];

describe('TestMap.vue', () => {
    it('renders each stage and part status', () => {
        const wrapper = mount(TestMap, { props: { testMap } });

        expect(wrapper.text()).toContain('test_map');
        expect(wrapper.text()).toContain('stage 1');
        expect(wrapper.text()).toContain('stage 2');
        expect(wrapper.text()).toContain('part 1');
        expect(wrapper.text()).toContain('part 2');
        expect(wrapper.find('[aria-current="step"]').exists()).toBe(true);
        expect(wrapper.findAll('.line-through')).toHaveLength(0);
    });
});
