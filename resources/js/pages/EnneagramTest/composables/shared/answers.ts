import type { SelectedAnswer } from './types';

export function answerCategory(answer: SelectedAnswer): string {
    return String(answer.category || answer.key);
}

export function answerCategories(answers: SelectedAnswer[]): string[] {
    return answers.map(answerCategory);
}

export function countDuplicateAnswerCategories(answers: SelectedAnswer[]): number {
    const categories = answerCategories(answers);
    const uniqueCategories = new Set(categories);

    return answers.length - uniqueCategories.size;
}
