import { ref, type Ref } from 'vue';
import { SINGLE_ANSWER_AUTO_CONFIRM_DELAY_MS } from './constants';
import type { SelectedAnswer } from './types';

interface Options {
    maxAnswers: Ref<number>;
    onAutoConfirm: () => void;
    /** Master switch to enable/disable auto-confirm for single-answer questions (controlled from UI). */
    enableAutoConfirmSingle?: Ref<boolean>;
    /** If > 0, auto-confirm for single-answer questions is deferred (used by Stage 1). */
    autoConfirmDelayMs?: Ref<number> | number;
}

export function useAnswerSelection({ maxAnswers, onAutoConfirm, enableAutoConfirmSingle, autoConfirmDelayMs = 0 }: Options) {
    const selectedAnswers = ref<SelectedAnswer[]>([]);

    function isSelected(key: string | number): boolean {
        return selectedAnswers.value.some((a) => String(a.key) === String(key));
    }

    function toggleAnswer(key: string | number, value: string, category: string | number = '') {
        const idx = selectedAnswers.value.findIndex((a) => String(a.key) === String(key));
        const answer: SelectedAnswer = { key, value, category: String(category) };

        if (idx > -1) {
            selectedAnswers.value.splice(idx, 1);
        } else if (selectedAnswers.value.length < maxAnswers.value) {
            selectedAnswers.value.push(answer);
        } else if (maxAnswers.value === 1) {
            selectedAnswers.value = [answer];
        }

        const autoEnabled = enableAutoConfirmSingle?.value ?? true;
        if (autoEnabled && maxAnswers.value === 1 && selectedAnswers.value.length === 1) {
            const delay = typeof autoConfirmDelayMs === 'number' ? autoConfirmDelayMs : autoConfirmDelayMs.value;
            if (delay > 0) {
                setTimeout(onAutoConfirm, delay ?? SINGLE_ANSWER_AUTO_CONFIRM_DELAY_MS);
            } else {
                onAutoConfirm();
            }
        }
    }

    function clear() {
        selectedAnswers.value = [];
    }

    return { selectedAnswers, isSelected, toggleAnswer, clear };
}
