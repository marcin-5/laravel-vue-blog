import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import QuestionCard from '../QuestionCard.vue';

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', inheritAttrs: false, template: '<button v-bind="$attrs"><slot /></button>' },
}));

vi.mock('@/components/ui/card', () => ({
    Card: { template: '<div><slot /></div>' },
    CardContent: { template: '<div><slot /></div>' },
    CardHeader: { template: '<div><slot /></div>' },
    CardTitle: { template: '<div><slot /></div>' },
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

describe('QuestionCard.vue', () => {
    it('labels selected answers and shows the skip counter beside them', () => {
        const wrapper = mount(QuestionCard, {
            props: {
                question: { id: 'question-1', question: 'Question' },
                options: [{ key: 'option-1', value: 'Answer', category: 'sp' }],
                selectedAnswers: [],
                answerLimit: 1,
                skipCount: 0,
                skipLimit: 1,
                canSkip: true,
                canBack: false,
                autoConfirmSingle: false,
                processing: false,
            },
        });

        const header = wrapper.find('.answer-progress');

        expect(header.text()).toContain('answered_questions: 0/1');
        expect(header.text()).toContain('skips: 0/1');
    });

    it('allows long answer text to grow across multiple lines', () => {
        const wrapper = mount(QuestionCard, {
            props: {
                question: { id: 'question-1', question: 'Question' },
                options: [{ key: 'option-1', value: 'A long answer that needs more than two lines on mobile', category: 'sp' }],
                selectedAnswers: [],
                answerLimit: 1,
                skipCount: 0,
                skipLimit: 1,
                canSkip: true,
                canBack: false,
                autoConfirmSingle: false,
                processing: false,
            },
        });

        const answerButton = wrapper.find('[aria-pressed]');

        expect(answerButton.classes()).toEqual(expect.arrayContaining(['h-auto', 'min-h-10', 'items-start', 'whitespace-normal']));
    });

    it('emits only the fields accepted by the answer API', async () => {
        const option = { key: 'option-1', value: 'Answer', category: '1', score: 999 };
        const wrapper = mount(QuestionCard, {
            props: {
                question: { id: 'question-1', question: 'Question' },
                options: [option],
                selectedAnswers: [],
                answerLimit: 1,
                skipCount: 0,
                skipLimit: 1,
                canSkip: true,
                canBack: false,
                autoConfirmSingle: false,
                processing: false,
            },
        });

        await wrapper.find('[aria-pressed]').trigger('click');
        await wrapper.findAll('button').at(-1)?.trigger('click');

        expect(wrapper.emitted('answer')).toEqual([[[{ key: 'option-1', value: 'Answer', category: '1' }]]]);
    });
});
