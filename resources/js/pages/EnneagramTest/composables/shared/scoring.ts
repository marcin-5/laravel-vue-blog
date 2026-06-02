import { type EnneagramType, TYPE_IDS } from './constants';
import { type Instinct, INSTINCTS, type InstinctScores, type LeadIndicator } from './types';

export function createEmptyInstinctScores(): InstinctScores {
    return { sp: 0, so: 0, sx: 0 };
}

export function createEmptyTypeScores(): Record<EnneagramType, number> {
    return TYPE_IDS.reduce(
        (scores, type) => {
            scores[type] = 0;

            return scores;
        },
        {} as Record<EnneagramType, number>,
    );
}

export function cloneScoresPerPart<K extends string>(scores: Record<number, Record<K, number>>): Record<number, Record<K, number>> {
    return Object.fromEntries(Object.entries(scores).map(([part, partScores]) => [Number(part), { ...partScores }])) as Record<
        number,
        Record<K, number>
    >;
}

export function incrementScore<K extends string>(scores: Record<K, number>, key: K, amount = 1): void {
    scores[key] = (scores[key] ?? 0) + amount;
}

/**
 * Checks if the difference between the leader and the second place meets the threshold.
 */
export function hasLead<K extends string>(scores: Record<K, number>, threshold: number, keys?: readonly K[]): boolean {
    if (!threshold || threshold <= 0) {
        return false;
    }
    const values = keys ? keys.map((k) => scores[k] ?? 0).sort((a, b) => b - a) : Object.values(scores).sort((a, b) => (b as number) - (a as number));

    if (values.length < 2) {
        return (values[0] as number) >= threshold;
    }
    return (values[0] as number) - (values[1] as number) >= threshold;
}

/**
 * Checks if there's a tie between the top two scores.
 */
export function isTopTwoTie<K extends string>(scores: Record<K, number>, keys?: readonly K[]): boolean {
    const values = keys ? keys.map((k) => scores[k] ?? 0).sort((a, b) => b - a) : Object.values(scores).sort((a, b) => (b as number) - (a as number));

    if (values.length < 2) {
        return false;
    }
    return (values[0] as number) === (values[1] as number);
}

/**
 * Returns scores sorted by value in descending order.
 */
export function getSortedScores<K extends string>(scores: Record<K, number>): { key: K; score: number }[] {
    return (Object.entries(scores) as [K, number][]).map(([key, score]) => ({ key, score })).sort((a, b) => b.score - a.score);
}

export function buildTopThreeLeadIndicators<K extends string>(
    scores: Record<K, number>,
    labels: {
        leaderVsSecond: string;
        secondVsThird: string;
    },
    targets: {
        leaderVsSecond: number;
        secondVsThird: number;
    },
): LeadIndicator[] {
    const [leader, second, third] = getSortedScores(scores);

    return [
        {
            label: labels.leaderVsSecond,
            current: leader.score - second.score,
            target: targets.leaderVsSecond,
            color: 'bg-secondary-foreground',
        },
        {
            label: labels.secondVsThird,
            current: second.score - third.score,
            target: targets.secondVsThird,
            color: 'bg-foreground',
        },
    ];
}

/**
 * Returns the key with the highest score.
 */
export function getLeader<K extends string>(scores: Record<K, number>, keys?: readonly K[]): K {
    const activeKeys = keys ?? (Object.keys(scores) as K[]);
    if (activeKeys.length === 0) {
        throw new Error('Cannot get leader from empty scores');
    }
    return activeKeys.reduce((best, k) => ((scores[k] ?? 0) > (scores[best] ?? 0) ? k : best), activeKeys[0]);
}

/**
 * Secondary instinct = the one that is neither dominant (winner of P1)
 * nor weakest (winner of P2, which asks about the LEAST important instinct).
 */
export function determineSecondaryInstinct(dominant: Instinct | null, p2Scores: InstinctScores): Instinct {
    const weakest = getLeader(p2Scores, INSTINCTS);
    const middle = INSTINCTS.find((i) => i !== dominant && i !== weakest);
    return middle ?? INSTINCTS.find((i) => i !== dominant) ?? 'so';
}
