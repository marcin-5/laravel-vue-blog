import { computed, ref, watch } from 'vue';
import { SINGLE_ANSWER_AUTO_CONFIRM_DELAY_MS } from './shared/constants';
import {
    createEmptyInstinctScores,
    determineSecondaryInstinct,
    getLeader,
    hasLead,
    isTopTwoTie
} from './shared/scoring';
import { buildShuffledFlatOptions, shuffleByPriority } from './shared/shuffle';
import type { FlatOption, Instinct, InstinctScores } from './shared/types';
import { useAnswerSelection } from './shared/useAnswerSelection';
import { useHistory } from './shared/useHistory';

export interface Question {
    id: string | number;
    part: number;
    question: string;
    answerLists: Record<string, string | string[]>;
    stage?: number;
    priority?: number;
}

export interface PartConfig {
    desc: string;
    answersPerQuestion: number;
    maxSkips: number;
    fixedQuestions: number;
    thresholdX?: number;
    thresholdY?: number;
    maxQuestions?: number;
}

export interface Config {
    part1: PartConfig;
    part2: PartConfig;
}

export interface CompleteResults {
    scoresPart1: InstinctScores;
    scoresPart2: InstinctScores;
    part1Winner: Instinct | null;
    dominant: Instinct | null;
    secondary: Instinct;
}

interface Stage1Snapshot {
    part: number;
    index: number;
    scoresPart1: InstinctScores;
    scoresPart2: InstinctScores;
    answered1: number;
    answered2: number;
    part1Winner: Instinct | null;
    extraAskedPart2: boolean;
}

type EmitFn = (event: 'complete', results: CompleteResults) => void;

export function useEnneagramStage1(questions: Question[], config: Config, emit: EmitFn) {
    // --- State ---
    const currentPart = ref(1);
    const currentIndex = ref(0);
    const skips = ref(0);
    const scoresPart1 = ref<InstinctScores>(createEmptyInstinctScores());
    const scoresPart2 = ref<InstinctScores>(createEmptyInstinctScores());
    const answeredCountPart1 = ref(0);
    const answeredCountPart2 = ref(0);
    const part1Winner = ref<Instinct | null>(null);
    const extraAskedPart2 = ref(false);
    const shuffledPerQuestion = ref<Record<string, FlatOption[]>>({});

    const poolPart1 = shuffleByPriority<Question>(questions.filter((q) => q.part === 1));
    const poolPart2 = shuffleByPriority<Question>(questions.filter((q) => q.part === 2));

    // --- Computed ---
    const currentConfig = computed(() => (currentPart.value === 1 ? config.part1 : config.part2));
    const currentScores = computed(() => (currentPart.value === 1 ? scoresPart1.value : scoresPart2.value));
    const maxAnswersPerQuestion = computed(() => Number(currentConfig.value.answersPerQuestion ?? 1));
    const partQuestions = computed(() => (currentPart.value === 1 ? poolPart1 : poolPart2));
    const currentQuestion = computed(() => partQuestions.value[currentIndex.value]);

    const formattedDesc = computed(() =>
        (currentConfig.value.desc || '')
            .replace(/%answersPerQuestion/g, String(maxAnswersPerQuestion.value))
            .replace(/%maxSkips/g, String(currentConfig.value.maxSkips))
            .replace(/%fixedQuestions/g, String(currentConfig.value.fixedQuestions)),
    );

    const flatShuffledOptions = computed<FlatOption[]>(() => {
        const q = currentQuestion.value;
        if (!q?.answerLists) return [];
        return shuffledPerQuestion.value[questionKey(q)] ?? [];
    });

    // --- Submodules ---
    const {
        selectedAnswers,
        isSelected,
        toggleAnswer,
        clear: clearSelection,
    } = useAnswerSelection({
        maxAnswers: maxAnswersPerQuestion,
        onAutoConfirm: () => confirmAnswers(),
        autoConfirmDelayMs: SINGLE_ANSWER_AUTO_CONFIRM_DELAY_MS,
    });

    const {
        history,
        recordAnswer,
        recordSkip,
        pop: popHistory,
    } = useHistory<Stage1Snapshot>(
        () => ({
            part: currentPart.value,
            index: currentIndex.value,
            scoresPart1: { ...scoresPart1.value },
            scoresPart2: { ...scoresPart2.value },
            answered1: answeredCountPart1.value,
            answered2: answeredCountPart2.value,
            part1Winner: part1Winner.value,
            extraAskedPart2: extraAskedPart2.value,
        }),
        (s) => {
            currentPart.value = s.part;
            currentIndex.value = s.index;
            scoresPart1.value = { ...s.scoresPart1 };
            scoresPart2.value = { ...s.scoresPart2 };
            answeredCountPart1.value = s.answered1;
            answeredCountPart2.value = s.answered2;
            part1Winner.value = s.part1Winner;
            extraAskedPart2.value = s.extraAskedPart2;
        },
    );

    const canSkip = computed(() => selectedAnswers.value.length === 0 && skips.value < currentConfig.value.maxSkips);

    // --- Helpers ---
    function questionKey(q: Question): string {
        return String(q.id ?? `${q.stage}-${q.part}-${currentIndex.value}`);
    }

    function isLastInPart(): boolean {
        return currentIndex.value >= partQuestions.value.length - 1;
    }

    function advanceIndex(): void {
        currentIndex.value = Math.min(currentIndex.value + 1, partQuestions.value.length - 1);
    }

    function moveToPart2(): void {
        part1Winner.value = getLeader(scoresPart1.value);
        currentPart.value = 2;
        currentIndex.value = 0;
        skips.value = 0;
        extraAskedPart2.value = false;
        clearSelection();
    }

    function buildResults(): CompleteResults {
        return {
            scoresPart1: { ...scoresPart1.value },
            scoresPart2: { ...scoresPart2.value },
            part1Winner: part1Winner.value,
            dominant: part1Winner.value,
            secondary: determineSecondaryInstinct(part1Winner.value, scoresPart2.value),
        };
    }

    // --- Actions ---
    function confirmAnswers() {
        recordAnswer(currentPart.value, selectedAnswers.value, skips.value);

        const target = currentPart.value === 1 ? scoresPart1.value : scoresPart2.value;
        for (const answer of selectedAnswers.value) {
            const category = String(answer.category || answer.key);
            if (target[category as Instinct] !== undefined) {
                target[category as Instinct] += 1;
            }
        }

        if (currentPart.value === 1) answeredCountPart1.value += 1;
        else answeredCountPart2.value += 1;

        clearSelection();
        advanceFlowAfterAnswer();
    }

    function shouldEndPart1(): boolean {
        const reachedLead = hasLead(scoresPart1.value, Number(config.part1.thresholdX ?? 0));
        const reachedMax = answeredCountPart1.value >= Number(config.part1.maxQuestions ?? 0);
        return reachedLead || reachedMax || isLastInPart();
    }

    function shouldEndPart2(): boolean {
        const endByX = hasLead(scoresPart2.value, Number(config.part2.thresholdX ?? 0));
        const specialYApplicable = part1Winner.value != null && (scoresPart2.value[part1Winner.value] ?? 0) === 0;
        const endByY = specialYApplicable && hasLead(scoresPart2.value, Number(config.part2.thresholdY ?? 0));
        const reachedMax = answeredCountPart2.value >= Number(config.part2.maxQuestions ?? 0);
        return endByX || endByY || reachedMax || isLastInPart();
    }

    function shouldAskExtraTieBreaker(): boolean {
        const reachedMax = answeredCountPart2.value >= Number(config.part2.maxQuestions ?? 0);
        return (
            reachedMax &&
            !extraAskedPart2.value &&
            isTopTwoTie(scoresPart2.value) &&
            !isLastInPart() &&
            !hasLead(scoresPart2.value, Number(config.part2.thresholdX ?? 0))
        );
    }

    function advanceFlowAfterAnswer() {
        if (currentPart.value === 1) {
            if (shouldEndPart1()) moveToPart2();
            else advanceIndex();
            return;
        }

        if (shouldAskExtraTieBreaker()) {
            extraAskedPart2.value = true;
            advanceIndex();
            return;
        }

        if (shouldEndPart2()) {
            emit('complete', buildResults());
            return;
        }

        advanceIndex();
    }

    function handleSkip() {
        if (skips.value >= currentConfig.value.maxSkips) return;

        recordSkip(currentPart.value, skips.value);
        skips.value++;

        if (!isLastInPart()) {
            currentIndex.value++;
            return;
        }

        if (currentPart.value === 1) moveToPart2();
        else emit('complete', buildResults());
    }

    function goBack() {
        const last = popHistory();
        if (!last) return;
        skips.value = last.skipsAtThisPoint;
        selectedAnswers.value = last.type === 'answer' ? [...last.answers] : [];
    }

    // Lazily build & cache shuffled options per question.
    watch(
        [currentPart, currentIndex],
        () => {
            const q = currentQuestion.value;
            if (!q?.answerLists) return;
            const key = questionKey(q);
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
        maxAnswersPerQuestion,
        formattedDesc,
        partQuestions,
        currentQuestion,
        flatShuffledOptions,
        canSkip,
        // Methods
        isSelected,
        toggleAnswer,
        confirmAnswers,
        handleSkip,
        goBack,
    };
}

export type { FlatOption } from './shared/types';
