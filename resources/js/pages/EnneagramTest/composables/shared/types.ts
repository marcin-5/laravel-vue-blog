export type Instinct = 'sp' | 'so' | 'sx';

export const INSTINCTS: readonly Instinct[] = ['sp', 'so', 'sx'] as const;

export interface FlatOption {
    key: string;
    value: string;
    category: string;
}

export interface SelectedAnswer {
    key: string | number;
    value: string;
    category: string;
}

export interface BaseHistoryItem<TSnapshot> {
    part: number;
    type: 'answer' | 'skip';
    answers: SelectedAnswer[];
    skipsAtThisPoint: number;
    snapshot: TSnapshot;
}

export type InstinctScores = Record<Instinct, number>;
