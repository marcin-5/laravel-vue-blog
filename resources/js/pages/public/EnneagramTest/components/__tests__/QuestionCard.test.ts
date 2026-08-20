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
});
