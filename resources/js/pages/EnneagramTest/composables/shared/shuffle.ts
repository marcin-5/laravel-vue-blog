import type { FlatOption } from './types';

/** Fisher-Yates in-place shuffle. Returns the same (mutated) array for chaining. */
export function fisherYates<T>(arr: T[]): T[] {
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

interface AnswerListHolder {
    answerLists: Record<string, string | string[]>;
}

/**
 * Flattens `answerLists` into a shuffled list of options.
 * - When a value is an array, each element becomes a separate option (keyed `category-<idx>`).
 * - When a value is a scalar, it becomes a single option keyed by its category.
 */
export function buildShuffledFlatOptions(q: AnswerListHolder): FlatOption[] {
    const flat: FlatOption[] = [];

    for (const [category, options] of Object.entries(q.answerLists)) {
        if (Array.isArray(options)) {
            options.forEach((option, idx) => {
                flat.push({ key: `${category}-${idx}`, value: String(option), category });
            });
        } else {
            flat.push({ key: category, value: String(options), category });
        }
    }

    return fisherYates(flat);
}

export interface QuestionWithPriority {
    id: string | number;
    priority?: number;
}

/**
 * Shuffles questions based on their priority.
 * Higher priority means a higher chance of being earlier in the list.
 */
export function shuffleByPriority<T extends QuestionWithPriority>(questions: T[]): T[] {
    const weightedIds: (string | number)[] = [];

    for (const q of questions) {
        const weight = (q.priority ?? 0) + 1;
        for (let i = 0; i < weight; i++) {
            weightedIds.push(q.id);
        }
    }

    fisherYates(weightedIds);

    const uniqueIds = Array.from(new Set(weightedIds));
    return uniqueIds.map((id) => questions.find((q) => q.id === id)!);
}
