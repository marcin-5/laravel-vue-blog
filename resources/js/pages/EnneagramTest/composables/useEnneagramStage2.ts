import { computed, ref, type Ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { answerCategory, countDuplicateAnswerCategories } from './shared/answers';
import { type EnneagramType } from './shared/constants';
import {
    buildTopThreeLeadIndicators,
    cloneScoresPerPart,
    createEmptyTypeScores,
    hasLead as hasScoringLead,
    incrementScore,
    isTopTwoTie as isScoringTiedAtTop,
} from './shared/scoring';
import { buildShuffledFlatOptions, shuffleByPriority } from './shared/shuffle';
import type { CompleteStage1Results, Config, FlatOption, Instinct, PartConfig, Question, SelectedAnswer, Stage2Results } from './shared/types';
import { type BaseStageState, useBaseEnneagramStage } from './shared/useBaseEnneagramStage';

interface Stage2Snapshot {
    part: number;
    index: number;
    typeScores: Record<EnneagramType, number>;
    scoresPerPart: Record<number, Record<EnneagramType, number>>;
    selectedInPart1: string[];
    selectedInPart3: string[];
    skips: number;
    poolIndex: number;
    instinct: Instinct;
    bonusPointsPerPart: Record<number, number>;
}

type Stage2Emit = (event: 'complete', results: Stage2Results) => void;
type StageTransitionState = Pick<BaseStageState, 'currentPart' | 'skips'>;
type StageFlowState = Pick<BaseStageState, 'currentPart' | 'skips' | 'currentConfig'>;

interface Stage2Instincts {
    dominant: Instinct;
    secondary: Instinct;
}

interface Stage2Pools {
    dominant: Question[];
    secondary: Question[];
}

const DEFAULT_DOMINANT: Instinct = 'sp';
const DEFAULT_SECONDARY: Instinct = 'so';
const LAST_PART = 4;

function resolveStage2Instincts(resultsStage1: CompleteStage1Results): Stage2Instincts {
    if (resultsStage1.isUnresolvable) {
        return {
            dominant: DEFAULT_DOMINANT,
            secondary: DEFAULT_SECONDARY,
        };
    }

    return {
        dominant: resultsStage1.dominant ?? DEFAULT_DOMINANT,
        secondary: resultsStage1.secondary ?? DEFAULT_SECONDARY,
    };
}

function createStage2Pools(questions: Question[], instincts: Stage2Instincts): Stage2Pools {
    return {
        dominant: shuffleByPriority(questions.filter((question) => question.id.startsWith(`${instincts.dominant}-`))),
        secondary: shuffleByPriority(questions.filter((question) => question.id.startsWith(`${instincts.secondary}-`))),
    };
}

export function useEnneagramStage2(
    questions: Question[],
    config: Config['stages']['stage2'],
    resultsStage1: CompleteStage1Results,
    emit?: Stage2Emit,
    enableAutoConfirmSingle?: Ref<boolean>,
) {
    const instincts = resolveStage2Instincts(resultsStage1);
    const pools = createStage2Pools(questions, instincts);

    const { t } = useI18n();

    // --- State ---
    const currentIndex = ref(0);
    const typeScores = ref<Record<EnneagramType, number>>(createEmptyTypeScores());
    const scoresPerPart = ref<Record<number, Record<EnneagramType, number>>>({
        1: createEmptyTypeScores(),
        2: createEmptyTypeScores(),
        3: createEmptyTypeScores(),
        4: createEmptyTypeScores(),
    });
    const selectedInPart1 = ref<Set<string>>(new Set());
    const selectedInPart3 = ref<Set<string>>(new Set());
    const bonusPointsPerPart = ref<Record<number, number>>({ 1: 0, 2: 0, 3: 0, 4: 0 });

    const instinctPoolIndices = ref<Record<string, number>>({
        [instincts.dominant]: 0,
        [instincts.secondary]: 0,
    });

    // --- Helpers ---
    const getInstinct = (part: number) => (part <= 2 ? instincts.dominant : instincts.secondary);
    const getPartQuestions = (part: number) => (part <= 2 ? pools.dominant : pools.secondary);

    function hasTieBreakingLead(scores: Record<EnneagramType, number>, config: PartConfig): boolean {
        const requiredLead = config.minLead ?? 2;
        return hasScoringLead(scores, requiredLead);
    }

    function isCurrentPartTieBreaker(part: number): boolean {
        return part === 2 || part === 4;
    }

    function shouldContinueForTieBreaking(part: number, config: PartConfig): boolean {
        if (!isCurrentPartTieBreaker(part)) return false;
        const instinct = getInstinct(part);
        const pool = getPartQuestions(part);
        const noMoreQuestions = instinctPoolIndices.value[instinct] >= pool.length;
        if (noMoreQuestions) return false;
        return !hasTieBreakingLead(scoresPerPart.value[part], config);
    }

    function applyAnswersToScores(answers: SelectedAnswer[], part: number) {
        for (const answer of answers) {
            const category = answerCategory(answer) as EnneagramType;

            incrementScore(typeScores.value, category);
            incrementScore(scoresPerPart.value[part], category);

            if (part === 1) {
                selectedInPart1.value.add(category);
            }

            if (part === 3) {
                selectedInPart3.value.add(category);
            }
        }
    }

    function shouldSkipPart(part: number): boolean {
        if (part === 2) return selectedInPart1.value.size <= 1;
        if (part === 4) return selectedInPart3.value.size <= 1;
        return false;
    }

    function moveToNextAvailablePart(state: StageTransitionState): boolean {
        while (state.currentPart.value < LAST_PART) {
            state.currentPart.value++;

            if (!shouldSkipPart(state.currentPart.value)) {
                currentIndex.value = 0;
                state.skips.value = 0;
                return true;
            }
        }

        return false;
    }

    function completeStage2(): void {
        const isUnresolvable = isScoringTiedAtTop(typeScores.value);

        emit?.('complete', {
            typeScores: { ...typeScores.value },
            scoresPerPart: cloneScoresPerPart(scoresPerPart.value),
            isUnresolvable,
        });
    }

    function createStage2Snapshot(state: StageFlowState): Stage2Snapshot {
        const instinct = getInstinct(state.currentPart.value);

        return {
            part: state.currentPart.value,
            index: currentIndex.value,
            typeScores: { ...typeScores.value },
            scoresPerPart: cloneScoresPerPart(scoresPerPart.value),
            selectedInPart1: Array.from(selectedInPart1.value),
            selectedInPart3: Array.from(selectedInPart3.value),
            skips: state.skips.value,
            poolIndex: instinctPoolIndices.value[instinct],
            instinct,
            bonusPointsPerPart: { ...bonusPointsPerPart.value },
        };
    }

    function restoreStage2Snapshot(state: StageFlowState, snapshot: Stage2Snapshot): void {
        state.currentPart.value = snapshot.part;
        currentIndex.value = snapshot.index;
        state.skips.value = snapshot.skips;
        typeScores.value = { ...snapshot.typeScores };
        scoresPerPart.value = cloneScoresPerPart(snapshot.scoresPerPart);
        selectedInPart1.value = new Set(snapshot.selectedInPart1);
        selectedInPart3.value = new Set(snapshot.selectedInPart3);
        bonusPointsPerPart.value = { ...snapshot.bonusPointsPerPart };
        instinctPoolIndices.value[snapshot.instinct] = snapshot.poolIndex;
    }

    function handleStage2Confirm(state: StageFlowState, answers: SelectedAnswer[]): void {
        bonusPointsPerPart.value[state.currentPart.value] += countDuplicateAnswerCategories(answers);
        applyAnswersToScores(answers, state.currentPart.value);
    }

    function advanceStage2Flow(state: StageFlowState, isAnswer = true): void {
        const part = state.currentPart.value;
        const config = state.currentConfig.value;
        const instinct = getInstinct(part);
        const pool = getPartQuestions(part);

        instinctPoolIndices.value[instinct]++;
        if (isAnswer) {
            currentIndex.value++;
        }

        const reachedMax = currentIndex.value >= config.maxQuestions;
        const noMoreQuestions = instinctPoolIndices.value[instinct] >= pool.length;
        const canEndEarly = !isCurrentPartTieBreaker(part) && hasTieBreakingLead(scoresPerPart.value[part], config);

        if (!(reachedMax || noMoreQuestions || canEndEarly)) return;

        // For parts 2 and 4: keep asking if no type has +2 lead and pool not exhausted
        if (shouldContinueForTieBreaking(part, config)) return;

        if (moveToNextAvailablePart(state)) {
            return;
        }

        completeStage2();
    }

    const getPartConfig = (part: number) => config[`part${part}` as keyof typeof config];

    // --- Base Composable ---
    const base = useBaseEnneagramStage<Stage2Snapshot>(getPartConfig, (state) => ({
        createSnapshot: () => createStage2Snapshot(state),
        restoreSnapshot: (snapshot) => restoreStage2Snapshot(state, snapshot),
        onConfirm: (answers) => handleStage2Confirm(state, answers),
        onAdvance: (isAnswer) => advanceStage2Flow(state, isAnswer),
        enableAutoConfirmSingle,
    }));

    const {
        currentPart,
        skips,
        shuffledPerQuestion,
        currentConfig,
        selectedAnswers,
        toggleAnswer,
        history,
        canSkip,
        confirmAnswers,
        handleSkip: baseHandleSkip,
        goBack,
    } = base;

    // --- Computed ---
    const currentInstinct = computed<Instinct>(() => getInstinct(currentPart.value));

    const partQuestions = computed(() => getPartQuestions(currentPart.value));

    const currentQuestion = computed(() => partQuestions.value[instinctPoolIndices.value[currentInstinct.value]]);

    const maxAnswersPerQuestion = computed(() => currentConfig.value.answersPerQuestion);
    const minQuestions = computed(() => {
        const baseMin = currentConfig.value.minLead ?? 0;
        const bonus = bonusPointsPerPart.value[currentPart.value] ?? 0;
        return Math.max(0, baseMin - bonus);
    });

    const leads = computed(() => {
        const requiredLead = currentConfig.value.minLead ?? 2;

        return buildTopThreeLeadIndicators(
            scoresPerPart.value[currentPart.value],
            {
                leaderVsSecond: t('lead_leader_vs_second'),
                secondVsThird: t('lead_second_vs_third'),
            },
            {
                leaderVsSecond: requiredLead,
                secondVsThird: requiredLead,
            },
        );
    });

    // --- Flat options with per-part filtering ---
    const flatOptions = computed<FlatOption[]>(() => {
        const q = currentQuestion.value;
        if (!q) return [];

        if (!shuffledPerQuestion.value[q.id]) {
            shuffledPerQuestion.value[q.id] = buildShuffledFlatOptions(q);
        }
        const flat = shuffledPerQuestion.value[q.id];

        // Part 2: only categories chosen in Part 1.
        // Part 4: only categories chosen in Part 1 OR Part 3.
        switch (currentPart.value) {
            case 2:
                return flat.filter((opt) => selectedInPart1.value.has(opt.category));
            case 4:
                return flat.filter((opt) => selectedInPart1.value.has(opt.category) || selectedInPart3.value.has(opt.category));
            default:
                return flat;
        }
    });

    // --- Actions ---
    function handleSkip() {
        if (skips.value >= currentConfig.value.maxSkips) return;
        baseHandleSkip();
    }

    return {
        currentPart,
        currentIndex,
        history,
        skips,
        selectedAnswers,
        typeScores,
        scoresPerPart,
        currentConfig,
        currentQuestion,
        flatOptions,
        maxAnswersPerQuestion,
        minQuestions,
        leads,
        canSkip,
        toggleAnswer,
        confirmAnswers,
        handleSkip,
        goBack,
        currentInstinct,
        instinctPoolIndices,
        partQuestions,
    };
}

export type { FlatOption } from './shared/types';
