import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { SINGLE_ANSWER_AUTO_CONFIRM_DELAY_MS } from './shared/constants';
import { formatStageDescription } from './shared/formatters';
import { isStage1Part1Question, isStage1Part2Question } from './shared/questionIds';
import { createEmptyInstinctScores, determineSecondaryInstinct, getLeader, hasLead, isTopTwoTie } from './shared/scoring';
import { buildShuffledFlatOptions, shuffleByPriority } from './shared/shuffle';
import type { CompleteStage1Results, Config, FlatOption, Instinct, InstinctScores, Question } from './shared/types';
import { useAnswerSelection } from './shared/useAnswerSelection';
import { useHistory } from './shared/useHistory';

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

type EmitFn = (event: 'complete', results: CompleteStage1Results) => void;

export function useEnneagramStage1(questions: Question[], config: Config['stages']['stage1'], emit: EmitFn) {
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

    const poolPart1 = shuffleByPriority<Question>(questions.filter((q) => isStage1Part1Question(q)));
    const poolPart2 = shuffleByPriority<Question>(questions.filter((q) => isStage1Part2Question(q)));

    const { t } = useI18n();

    // --- Computed ---
    const currentConfig = computed(() => (currentPart.value === 1 ? config.part1 : config.part2));
    const currentScores = computed(() => (currentPart.value === 1 ? scoresPart1.value : scoresPart2.value));
    const maxAnswersPerQuestion = computed(() => {
        if (currentPart.value === 1 && answeredCountPart1.value >= Number(currentConfig.value.maxQuestions ?? 0)) {
            return 1;
        }
        return Number(currentConfig.value.answersPerQuestion ?? 1);
    });
    const partQuestions = computed(() => (currentPart.value === 1 ? poolPart1 : poolPart2));
    const currentQuestion = computed(() => partQuestions.value[currentIndex.value]);

    const formattedDesc = computed(() => {
        const descKey = `stage_descriptions.stage1.part${currentPart.value}`;
        return formatStageDescription(t(descKey), {
            answersPerQuestion: maxAnswersPerQuestion.value,
            maxSkips: currentConfig.value.maxSkips,
            fixedQuestions: currentConfig.value.fixedQuestions ?? 0,
        });
    });

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
        return String(q.id ?? `${currentPart.value}-${currentIndex.value}`);
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

    function buildResults(): CompleteStage1Results {
        // Unresolvable if exhausted all questions in Part 2 and still a tie, or same winner as Part 1 persists
        const reachedMax = answeredCountPart2.value >= Number(currentConfig.value.maxQuestions ?? 0);
        const exhausted = reachedMax || isLastInPart();
        const winnerPart2 = getLeader(scoresPart2.value);

        const isUnresolvable = exhausted && (isTopTwoTie(scoresPart2.value) || (part1Winner.value !== null && winnerPart2 === part1Winner.value));

        const base = {
            scoresPart1: { ...scoresPart1.value },
            scoresPart2: { ...scoresPart2.value },
            part1Winner: part1Winner.value,
        };

        if (isUnresolvable || part1Winner.value === null) {
            return {
                ...base,
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
            ...base,
            isUnresolvable: false,
            dominant,
            secondary,
            weakest,
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
        const thresholdX = Number(currentConfig.value.thresholdX ?? 0);
        const reachedLead = hasLead(scoresPart1.value, thresholdX);
        const reachedMax = answeredCountPart1.value >= Number(currentConfig.value.maxQuestions ?? 0);
        const isTie = isTopTwoTie(scoresPart1.value);

        // After reaching max questions, we only continue if there is a tie between the top two
        if (reachedMax && !reachedLead && isTie && !isLastInPart()) {
            return false;
        }

        return reachedLead || reachedMax || isLastInPart();
    }

    function shouldEndPart2(): boolean {
        const leader = getLeader(scoresPart2.value);
        const sameWinner = part1Winner.value !== null && leader === part1Winner.value;
        const reachedMax = answeredCountPart2.value >= Number(currentConfig.value.maxQuestions ?? 0);
        const thresholdX = Number(currentConfig.value.thresholdX ?? 0);
        const thresholdY = Number(currentConfig.value.thresholdY ?? 0);
        const endByX = !sameWinner && hasLead(scoresPart2.value, thresholdX);
        const specialYApplicable = part1Winner.value != null && (scoresPart2.value[part1Winner.value] ?? 0) === 0;
        const endByY = specialYApplicable && hasLead(scoresPart2.value, thresholdY);

        // If reached max questions but no conclusion yet (no X, no Y, or same winner), continue if possible
        if (reachedMax && !endByX && !endByY && !isLastInPart()) {
            return false;
        }

        return endByX || endByY || reachedMax || isLastInPart();
    }

    function shouldAskExtraTieBreaker(): boolean {
        const reachedMax = answeredCountPart2.value >= Number(currentConfig.value.maxQuestions ?? 0);
        return (
            reachedMax &&
            !extraAskedPart2.value &&
            isTopTwoTie(scoresPart2.value) &&
            !isLastInPart() &&
            !hasLead(scoresPart2.value, Number(currentConfig.value.thresholdX ?? 0))
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
