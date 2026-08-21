export type EnneagramLocale = 'pl' | 'en';

export type TestAction = 'answer' | 'skip' | 'back';

export interface EnneagramOption {
    key: string;
    value: string;
    category: string;
}

export interface EnneagramQuestion {
    id: string;
    question: string;
}

export type SelectedAnswer = EnneagramOption;

export type TestProgressPhase = 'standard' | 'tie_breaker';

export interface LeadProgress {
    value: number;
    target: number;
    alternativeTarget: number | null;
}

export interface TestProgress {
    current: number;
    total: number;
    answered: number;
    maximum: number;
    position: number;
    poolSize: number;
    target: number;
    phase: TestProgressPhase;
    tieBreakerStartedAt: number | null;
    lead: {
        firstSecond: LeadProgress;
        secondThird: LeadProgress;
    };
}

export type TestMapPartStatus = 'pending' | 'active' | 'completed' | 'skipped';

export interface TestMapPart {
    part: number;
    status: TestMapPartStatus;
}

export interface TestMapStage {
    stage: number;
    parts: TestMapPart[];
}

export interface AllowedActions {
    answer: boolean;
    skip: boolean;
    back: boolean;
}

export interface StageOneResult {
    scoresPart1: Record<string, number>;
    scoresPart2: Record<string, number>;
    part1Winner: string | null;
    dominant: string | null;
    secondary: string | null;
    weakest: string | null;
    isUnresolvable: boolean;
}

export interface StageTwoResult {
    typeScores: Record<string, number>;
    scoresPerPart: Record<string, Record<string, number>>;
    isUnresolvable: boolean;
}

export interface TestResult {
    stage1: StageOneResult | null;
    stage2: StageTwoResult | null;
}

export interface EnneagramTestState {
    version: string;
    locale: EnneagramLocale;
    status: 'in_progress' | 'completed';
    stage: number;
    part: number;
    question: EnneagramQuestion | null;
    options: EnneagramOption[];
    selected_answers: SelectedAnswer[];
    answer_limit: number;
    skip_count: number;
    skip_limit: number;
    progress: TestProgress;
    test_map: TestMapStage[];
    allowed_actions: AllowedActions;
    result: TestResult | null;
}

export interface TestApiResponse {
    contractVersion: string;
    testId: string;
    state: EnneagramTestState;
}

export interface StartTestPayload {
    extended: boolean;
}

export interface ActionTestPayload {
    testId: string;
    action: TestAction;
    answers: SelectedAnswer[];
}

export interface ResetTestResponse {
    contractVersion: string;
    testId: string;
    status: 'reset';
}

export interface EnneagramPageProps {
    initialLocale: EnneagramLocale;
    appDebug: boolean;
    autoConfirmSingleDefault: boolean;
    seo: {
        title: string;
        description: string;
    };
}
