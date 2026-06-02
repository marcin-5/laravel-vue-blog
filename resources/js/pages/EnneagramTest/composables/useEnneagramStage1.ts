import { computed, ref, type Ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { answerCategory, countDuplicateAnswerCategories } from './shared/answers';
import { SINGLE_ANSWER_AUTO_CONFIRM_DELAY_MS } from './shared/constants';
import { isStage1Part1Question, isStage1Part2Question } from './shared/questionIds';
import {
    buildTopThreeLeadIndicators,
    createEmptyInstinctScores,
    determineSecondaryInstinct,
    getLeader,
    hasLead,
    incrementScore,
    isTopTwoTie
} from './shared/scoring';
import { buildShuffledFlatOptions, shuffleByPriority } from './shared/shuffle';
import type {
    CompleteStage1Results,
    Config,
    FlatOption,
    Instinct,
    InstinctScores,
    PartConfig,
    Question,
    SelectedAnswer
} from './shared/types';
import { type BaseStageState, useBaseEnneagramStage } from './shared/useBaseEnneagramStage';

interface Stage1Snapshot {
    part: number;
    index: number;
    scoresPart1: InstinctScores;
    scoresPart2: InstinctScores;
    answered1: number;
    answered2: number;
    part1Winner: Instinct | null;
    extraAskedPart2: boolean;
    doubleAnswersCountPart1: number;
}

type EmitFn = (event: 'complete', results: CompleteStage1Results) => void;
type StageTransitionState = Pick<BaseStageState, 'currentPart' | 'skips'>;
type StageFlowState = Pick<BaseStageState, 'currentPart' | 'skips' | 'currentConfig'>;

function incrementScores(target: InstinctScores, answers: SelectedAnswer[]): void {
    for (const answer of answers) {
        const category = answerCategory(answer) as Instinct;

        if (target[category] !== undefined) {
            incrementScore(target, category);
        }
    }
}

export function useEnneagramStage1(questions: Question[], config: Config['stages']['stage1'], emit: EmitFn, enableAutoConfirmSingle?: Ref<boolean>) {
    // --- State ---
    const currentIndex = ref(0);
    const scoresPart1 = ref<InstinctScores>(createEmptyInstinctScores());
    const scoresPart2 = ref<InstinctScores>(createEmptyInstinctScores());
    const answeredCountPart1 = ref(0);
    const answeredCountPart2 = ref(0);
    const part1Winner = ref<Instinct | null>(null);
    const extraAskedPart2 = ref(false);
    const doubleAnswersCountPart1 = ref(0);

    const poolPart1 = shuffleByPriority<Question>(questions.filter((q) => isStage1Part1Question(q)));
    const poolPart2 = shuffleByPriority<Question>(questions.filter((q) => isStage1Part2Question(q)));

    const { t } = useI18n();

    // --- Helpers ---
    function questionKey(q: Question, part: number, index: number): string {
        return String(q.id ?? `${part}-${index}`);
    }

    function isLastInPart(index: number, pool: Question[]): boolean {
        return index >= pool.length - 1;
    }

    function advanceIndex(indexRef: Ref<number>, pool: Question[]): void {
        indexRef.value = Math.min(indexRef.value + 1, pool.length - 1);
    }

    function moveToPart2(state: StageTransitionState, clearSelection?: () => void): void {
        part1Winner.value = getLeader(scoresPart1.value);
        state.currentPart.value = 2;
        currentIndex.value = 0;
        state.skips.value = 0;
        extraAskedPart2.value = false;
        clearSelection?.();
    }

    function buildResults(index: number, config: PartConfig, pool: Question[]): CompleteStage1Results {
        const reachedMax = answeredCountPart2.value >= Number(config.maxQuestions ?? 0);
        const exhausted = reachedMax || isLastInPart(index, pool);
        const winnerPart2 = getLeader(scoresPart2.value);

        const isUnresolvable = exhausted && (isTopTwoTie(scoresPart2.value) || (part1Winner.value !== null && winnerPart2 === part1Winner.value));

        const baseResults = {
            scoresPart1: { ...scoresPart1.value },
            scoresPart2: { ...scoresPart2.value },
            part1Winner: part1Winner.value,
        };

        if (isUnresolvable || part1Winner.value === null) {
            return {
                ...baseResults,
                isUnresolvable: true,
                dominant: null,
                secondary: null,
                weakest: null,
            };
        }

        const dominant = part1Winner.value;
        const secondary = determineSecondaryInstinct(dominant, scoresPart2.value);
        const weakest = (['sp', 'so', 'sx'] as Instinct[]).find((i) => i !== dominant && i !== secondary)!;

        return {
            ...baseResults,
            isUnresolvable: false,
            dominant,
            secondary,
            weakest,
        };
    }

    function shouldEndPart1(config: PartConfig, index: number): boolean {
        const minLead = Number(config.minLead ?? 0);
        const reachedLead = hasLead(scoresPart1.value, minLead);
        const reachedMax = answeredCountPart1.value >= Number(config.maxQuestions ?? 0);
        const isTie = isTopTwoTie(scoresPart1.value);

        if (reachedMax && !reachedLead && isTie && !isLastInPart(index, poolPart1)) {
            return false;
        }

        return reachedLead || reachedMax || isLastInPart(index, poolPart1);
    }

    function shouldEndPart2(config: PartConfig, index: number): boolean {
        const leader = getLeader(scoresPart2.value);
        const sameWinner = part1Winner.value !== null && leader === part1Winner.value;
        const reachedMax = answeredCountPart2.value >= Number(config.maxQuestions ?? 0);
        const minLead = Number(config.minLead ?? 0);
        const minLeadAlternative = Number(config.minLeadAlternative ?? 0);
        const isStandardLeadMet = !sameWinner && hasLead(scoresPart2.value, minLead);
        const isAlternativeLeadApplicable = part1Winner.value != null && (scoresPart2.value[part1Winner.value] ?? 0) === 0;
        const isAlternativeLeadMet = isAlternativeLeadApplicable && hasLead(scoresPart2.value, minLeadAlternative);

        if (reachedMax && !isStandardLeadMet && !isAlternativeLeadMet && !isLastInPart(index, poolPart2)) {
            return false;
        }

        return isStandardLeadMet || isAlternativeLeadMet || reachedMax || isLastInPart(index, poolPart2);
    }

    function shouldAskExtraTieBreaker(config: PartConfig, index: number): boolean {
        const reachedMax = answeredCountPart2.value >= Number(config.maxQuestions ?? 0);
        return (
            reachedMax &&
            !extraAskedPart2.value &&
            isTopTwoTie(scoresPart2.value) &&
            !isLastInPart(index, poolPart2) &&
            !hasLead(scoresPart2.value, Number(config.minLead ?? 0))
        );
    }

    function createStage1Snapshot(state: StageFlowState): Stage1Snapshot {
        return {
            part: state.currentPart.value,
            index: currentIndex.value,
            scoresPart1: { ...scoresPart1.value },
            scoresPart2: { ...scoresPart2.value },
            answered1: answeredCountPart1.value,
            answered2: answeredCountPart2.value,
            part1Winner: part1Winner.value,
            extraAskedPart2: extraAskedPart2.value,
            doubleAnswersCountPart1: doubleAnswersCountPart1.value,
        };
    }

    function restoreStage1Snapshot(state: StageFlowState, snapshot: Stage1Snapshot): void {
        state.currentPart.value = snapshot.part;
        currentIndex.value = snapshot.index;
        scoresPart1.value = { ...snapshot.scoresPart1 };
        scoresPart2.value = { ...snapshot.scoresPart2 };
        answeredCountPart1.value = snapshot.answered1;
        answeredCountPart2.value = snapshot.answered2;
        part1Winner.value = snapshot.part1Winner;
        extraAskedPart2.value = snapshot.extraAskedPart2;
        doubleAnswersCountPart1.value = snapshot.doubleAnswersCountPart1;
    }

    function handleStage1Confirm(state: StageFlowState, answers: SelectedAnswer[]): void {
        const isPart1 = state.currentPart.value === 1;

        if (isPart1) {
            doubleAnswersCountPart1.value += countDuplicateAnswerCategories(answers);
        }

        incrementScores(isPart1 ? scoresPart1.value : scoresPart2.value, answers);

        if (isPart1) {
            answeredCountPart1.value += 1;
        } else {
            answeredCountPart2.value += 1;
        }
    }

    function advanceStage1Flow(state: StageFlowState, isAnswer: boolean, clearSelection?: () => void): void {
        const part = state.currentPart.value;
        const config = state.currentConfig.value;

        if (part === 1) {
            if (shouldEndPart1(config, currentIndex.value)) {
                moveToPart2(state, clearSelection);
            } else {
                advanceIndex(currentIndex, poolPart1);
            }
            return;
        }

        if (isAnswer && shouldAskExtraTieBreaker(config, currentIndex.value)) {
            extraAskedPart2.value = true;
            advanceIndex(currentIndex, poolPart2);
            return;
        }

        if (shouldEndPart2(config, currentIndex.value)) {
            emit('complete', buildResults(currentIndex.value, config, poolPart2));
            return;
        }

        advanceIndex(currentIndex, poolPart2);
    }

    const getPartConfig = (part: number) => (part === 1 ? config.part1 : config.part2);

    // --- Base Composable ---
    const base = useBaseEnneagramStage<Stage1Snapshot>(getPartConfig, (state) => ({
        createSnapshot: () => createStage1Snapshot(state),
        restoreSnapshot: (snapshot) => restoreStage1Snapshot(state, snapshot),
        onConfirm: (answers) => handleStage1Confirm(state, answers),
        onAdvance: (isAnswer) => advanceStage1Flow(state, isAnswer),
        maxAnswersOverride: computed(() => {
            if (state.currentPart.value === 1 && answeredCountPart1.value >= Number(config.part1.maxQuestions ?? 0)) {
                return 1;
            }

            return Number(state.currentConfig.value.answersPerQuestion ?? 1);
        }),
        autoConfirmDelayMs: computed(() =>
            state.currentPart.value === 1 && answeredCountPart1.value >= Number(config.part1.maxQuestions ?? 0)
                ? 0
                : SINGLE_ANSWER_AUTO_CONFIRM_DELAY_MS,
        ),
        enableAutoConfirmSingle,
    }));

    const {
        currentPart,
        skips,
        shuffledPerQuestion,
        currentConfig,
        selectedAnswers,
        toggleAnswer,
        clearSelection,
        history,
        canSkip,
        confirmAnswers,
        handleSkip: baseHandleSkip,
        goBack,
    } = base;

    // --- Computed ---
    const currentScores = computed(() => (currentPart.value === 1 ? scoresPart1.value : scoresPart2.value));
    const maxAnswersPerQuestion = computed(() => {
        if (currentPart.value === 1 && answeredCountPart1.value >= Number(currentConfig.value.maxQuestions ?? 0)) {
            return 1;
        }
        return Number(currentConfig.value.answersPerQuestion ?? 1);
    });
    const partQuestions = computed(() => (currentPart.value === 1 ? poolPart1 : poolPart2));
    const currentQuestion = computed(() => partQuestions.value[currentIndex.value]);

    const formattedDesc = computed(() => t('max_answers', { maxAnswers: maxAnswersPerQuestion.value }));

    const minQuestions = computed(() => {
        const config = currentConfig.value;
        if (currentPart.value === 2 && part1Winner.value) {
            const leaderPart1ScoreInPart2 = scoresPart2.value[part1Winner.value] ?? 0;
            if (leaderPart1ScoreInPart2 === 0 && config.minLeadAlternative) {
                return config.minLeadAlternative;
            }
        }
        let min = config.minLead ?? 0;
        if (currentPart.value === 1) {
            min -= doubleAnswersCountPart1.value;
        }
        return Math.max(0, min);
    });

    const leads = computed(() => {
        const scores = currentScores.value;
        const config = currentConfig.value;

        let leaderVsSecondTarget = config.minLead ?? 0;

        if (currentPart.value === 2 && part1Winner.value && (scores[part1Winner.value] ?? 0) === 0 && config.minLeadAlternative) {
            leaderVsSecondTarget = config.minLeadAlternative;
        }

        return buildTopThreeLeadIndicators(
            scores,
            {
                leaderVsSecond: t('lead_leader_vs_second'),
                secondVsThird: t('lead_second_vs_third'),
            },
            {
                leaderVsSecond: leaderVsSecondTarget,
                secondVsThird: config.minLead ?? 0,
            },
        );
    });

    const flatShuffledOptions = computed<FlatOption[]>(() => {
        const q = currentQuestion.value;
        if (!q?.answerLists) return [];
        return shuffledPerQuestion.value[questionKey(q, currentPart.value, currentIndex.value)] ?? [];
    });

    function handleSkip() {
        if (skips.value >= currentConfig.value.maxSkips) {
            return;
        }

        if (!isLastInPart(currentIndex.value, partQuestions.value)) {
            baseHandleSkip();
            return;
        }

        // Specific logic for skip on last question
        if (currentPart.value === 1) {
            moveToPart2(base);
            clearSelection();
        } else {
            emit('complete', buildResults(currentIndex.value, currentConfig.value, partQuestions.value));
        }
    }

    // Lazily build & cache shuffled options per question.
    watch(
        [currentPart, currentIndex],
        () => {
            const q = currentQuestion.value;
            if (!q?.answerLists) return;
            const key = questionKey(q, currentPart.value, currentIndex.value);
            if (!shuffledPerQuestion.value[key]) {
                shuffledPerQuestion.value[key] = buildShuffledFlatOptions(q);
            }
        },
        { immediate: true },
    );

    return {
        // State
        currentPart,
        currentIndex,
        history,
        skips,
        selectedAnswers,
        scoresPart1,
        scoresPart2,
        answeredCountPart1,
        answeredCountPart2,
        part1Winner,
        // Computed
        currentScores,
        currentConfig,
        minQuestions,
        leads,
        maxAnswersPerQuestion,
        formattedDesc,
        partQuestions,
        currentQuestion,
        flatShuffledOptions,
        canSkip,
        // Methods
        toggleAnswer,
        confirmAnswers,
        handleSkip,
        goBack,
    };
}

export type { FlatOption } from './shared/types';
