import type { EnneagramType } from './constants';

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

export interface Stage1CompleteResults {
    scoresPart1: InstinctScores;
    scoresPart2: InstinctScores;
    part1Winner: Instinct | null;
    isUnresolvable: boolean;
}

export interface ResolvedStage1Results extends Stage1CompleteResults {
    isUnresolvable: false;
    dominant: Instinct;
    secondary: Instinct;
    weakest: Instinct;
}

export interface UnresolvedStage1Results extends Stage1CompleteResults {
    isUnresolvable: true;
    dominant: null;
    secondary: Instinct | null;
    weakest: Instinct | null;
}

export type CompleteStage1Results = ResolvedStage1Results | UnresolvedStage1Results;

export interface Stage2Results {
    typeScores: Record<EnneagramType, number>;
    scoresPerPart: Record<number, Record<EnneagramType, number>>;
    isUnresolvable?: boolean;
}

export interface PartConfig {
    maxQuestions: number;
    maxSkips: number;
    answersPerQuestion: number;
    desc?: string;
    threshold?: number;
    minLead?: number;
    minLeadAlternative?: number;
    fixedQuestions?: number;
}

export interface Stage1Config {
    part1: PartConfig;
    part2: PartConfig;
}

export interface Stage2Config {
    part1: PartConfig;
    part2: PartConfig;
    part3: PartConfig;
    part4: PartConfig;
}

export interface Question {
    id: string;
    question: string;
    priority?: number;
    answerLists: Record<string, string | string[]>;
}

export interface Config {
    stages: {
        stage1: Stage1Config;
        stage2: Stage2Config;
    };
}

export interface TestData {
    questions: Question[];
    testConfig: Config;
}
