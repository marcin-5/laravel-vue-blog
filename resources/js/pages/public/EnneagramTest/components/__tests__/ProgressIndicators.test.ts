import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import type { EnneagramTestState } from '../../types';
import ProgressIndicators from '../ProgressIndicators.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string, values?: Record<string, number>) => {
            if (key === 'pool_position') {
                return `Pool: ${values?.position} / ${values?.poolSize}`;
            }

            if (key === 'tie_breaker_started') {
                return `Tie-breaker from answer ${values?.answered}`;
            }

            return key;
        },
    }),
}));

const state: EnneagramTestState = {
    version: '1',
    locale: 'en',
    status: 'in_progress',
    stage: 1,
    part: 1,
    question: { id: 'di-01', question: 'Question' },
    options: [],
    selected_answers: [],
    answer_limit: 2,
    skip_count: 1,
    skip_limit: 1,
    progress: {
        current: 4,
        total: 12,
        answered: 6,
        maximum: 6,
        position: 4,
        poolSize: 12,
        target: 6,
        phase: 'standard',
        tieBreakerStartedAt: null,
        lead: {
            firstSecond: { value: 3, target: 4, alternativeTarget: null },
            secondThird: { value: 1, target: 4, alternativeTarget: null },
        },
    },
    test_map: [
        {
            stage: 1,
            parts: [
                { part: 1, status: 'active' },
                { part: 2, status: 'pending' },
            ],
        },
        {
            stage: 2,
            parts: [
                { part: 1, status: 'pending' },
                { part: 2, status: 'pending' },
                { part: 3, status: 'pending' },
                { part: 4, status: 'pending' },
            ],
        },
    ],
    allowed_actions: { answer: true, skip: true, back: false },
    result: null,
};

describe('ProgressIndicators.vue', () => {
    it('renders target, pool position, map, and lead values without skips', () => {
        const wrapper = mount(ProgressIndicators, { props: { state } });

        expect(wrapper.text()).toContain('6 / 6');
        expect(wrapper.text()).toContain('Pool: 4 / 12');
        expect(wrapper.text()).toContain('test_map');
        expect(wrapper.text()).toContain('3 / 4');
        expect(wrapper.text()).toContain('1 / 4');
        expect(wrapper.text()).not.toContain('skips');
        expect(wrapper.findAll('[role="progressbar"]')).toHaveLength(3);
    });

    it('shows the tie-breaker marker after the configured target', () => {
        const wrapper = mount(ProgressIndicators, {
            props: {
                state: {
                    ...state,
                    progress: {
                        ...state.progress,
                        answered: 7,
                        position: 5,
                        phase: 'tie_breaker',
                        tieBreakerStartedAt: 6,
                    },
                },
            },
        });

        expect(wrapper.text()).toContain('tie_breaker_active');
        expect(wrapper.text()).toContain('Tie-breaker from answer 6');
        expect(wrapper.find('[aria-valuenow="6"]').exists()).toBe(true);
    });

    it('shows an earlier lead target marker when it is available', () => {
        const wrapper = mount(ProgressIndicators, {
            props: {
                state: {
                    ...state,
                    stage: 1,
                    part: 2,
                    progress: {
                        ...state.progress,
                        lead: {
                            firstSecond: { value: 1, target: 3, alternativeTarget: 2 },
                            secondThird: { value: 0, target: 3, alternativeTarget: null },
                        },
                    },
                },
            },
        });

        expect(wrapper.text()).toContain('lead_alternative_target');
        expect(wrapper.text()).toContain('2');
        expect(wrapper.find('.lead-alternative-marker').exists()).toBe(true);
        expect(wrapper.find('.lead-alternative-marker').attributes('style')).toContain('left: 66.66666666666666%');
    });
});
