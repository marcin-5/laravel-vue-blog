import type { Instinct } from './types';

const STAGE_1_PART_1_PREFIX = 'di-';
const STAGE_1_PART_2_PREFIX = 'ri-';
const STAGE_2_PREFIXES: readonly Instinct[] = ['sp', 'so', 'sx'];

export interface QuestionIdHolder {
    id: string | number;
}

export function questionId(question: QuestionIdHolder): string {
    return String(question.id);
}

export function isStage1Part1Question(question: QuestionIdHolder): boolean {
    return questionId(question).startsWith(STAGE_1_PART_1_PREFIX);
}

export function isStage1Part2Question(question: QuestionIdHolder): boolean {
    return questionId(question).startsWith(STAGE_1_PART_2_PREFIX);
}

export function isStage1Question(question: QuestionIdHolder): boolean {
    return isStage1Part1Question(question) || isStage1Part2Question(question);
}

export function isStage2Question(question: QuestionIdHolder): boolean {
    return STAGE_2_PREFIXES.some((prefix) => questionId(question).startsWith(`${prefix}-`));
}
