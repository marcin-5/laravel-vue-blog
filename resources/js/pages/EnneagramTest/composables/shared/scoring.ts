import { type Instinct, INSTINCTS, type InstinctScores } from './types';

export function createEmptyInstinctScores(): InstinctScores {
    return { sp: 0, so: 0, sx: 0 };
}

export function hasLead(scores: InstinctScores, threshold: number): boolean {
    if (!threshold || threshold <= 0) return false;
    const values = INSTINCTS.map((k) => scores[k] ?? 0).sort((a, b) => b - a);
    return values[0] - values[1] >= threshold;
}

export function isTopTwoTie(scores: InstinctScores): boolean {
    const values = INSTINCTS.map((k) => scores[k] ?? 0).sort((a, b) => b - a);
    return values[0] === values[1];
}

export function getLeader(scores: InstinctScores): Instinct {
    return INSTINCTS.reduce((best, k) => ((scores[k] ?? 0) > (scores[best] ?? 0) ? k : best), INSTINCTS[0]);
}

/**
 * Secondary instinct = the one that is neither dominant (winner of P1)
 * nor weakest (winner of P2, which asks about the LEAST important instinct).
 */
export function determineSecondaryInstinct(dominant: Instinct | null, p2Scores: InstinctScores): Instinct {
    const weakest = getLeader(p2Scores);
    const middle = INSTINCTS.find((i) => i !== dominant && i !== weakest);
    return middle ?? INSTINCTS.find((i) => i !== dominant) ?? 'so';
}
