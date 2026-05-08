import { computed, ref } from 'vue';
import { type EnneagramType, TYPE_IDS } from './shared/constants';
import { buildShuffledFlatOptions, shuffleByPriority } from './shared/shuffle';
import type { CompleteStage1Results, Config, FlatOption, Instinct, Question, SelectedAnswer, Stage2Results } from './shared/types';
import { useAnswerSelection } from './shared/useAnswerSelection';
import { useHistory } from './shared/useHistory';

interface Stage2Snapshot {
    part: number;
    index: number;
    typeScores: Record<EnneagramType, number>;
    scoresPerPart: Record<number, Record<EnneagramType, number>>;
    selectedInPart1: string[]; // Sets are serialized as arrays for snapshot simplicity
    selectedInPart3: string[];
    skips: number;
    poolIndex: number;
    instinct: Instinct;
}

type Stage2Emit = (event: 'complete', results: Stage2Results) => void;

const DEFAULT_DOMINANT: Instinct = 'sp';
const DEFAULT_SECONDARY: Instinct = 'so';
const LAST_PART = 4;

function createEmptyTypeScores(): Record<EnneagramType, number> {
    return TYPE_IDS.reduce((acc, id) => ({ ...acc, [id]: 0 }), {} as Record<EnneagramType, number>);
}

export function useEnneagramStage2(
    questions: Question[],
    config: Config['stages']['stage2'],
    resultsStage1: CompleteStage1Results,
    emit?: Stage2Emit,
) {
    const dominantInstinct: Instinct = resultsStage1.isUnresolvable ? DEFAULT_DOMINANT : (resultsStage1.dominant ?? DEFAULT_DOMINANT);
    const secondaryInstinct: Instinct = resultsStage1.isUnresolvable ? DEFAULT_SECONDARY : (resultsStage1.secondary ?? DEFAULT_SECONDARY);

    // --- State ---
    const currentPart = ref(1);
    const currentIndex = ref(0);
    const skips = ref(0);
    const typeScores = ref<Record<EnneagramType, number>>(createEmptyTypeScores());
    const scoresPerPart = ref<Record<number, Record<EnneagramType, number>>>({
        1: createEmptyTypeScores(),
        2: createEmptyTypeScores(),
        3: createEmptyTypeScores(),
        4: createEmptyTypeScores(),
    });
    const selectedInPart1 = ref<Set<string>>(new Set());
    const selectedInPart3 = ref<Set<string>>(new Set());
    const shuffledAnswersPerQuestion = ref<Record<string, FlatOption[]>>({});

    const instinctPoolIndices = ref<Record<string, number>>({
        [dominantInstinct]: 0,
        [secondaryInstinct]: 0,
    });

    // --- Computed ---
    const currentInstinct = computed<Instinct>(() => (currentPart.value <= 2 ? dominantInstinct : secondaryInstinct));

    const currentConfig = computed(() => config[`part${currentPart.value}` as keyof typeof config]);

    const partQuestions = computed(() => (currentPart.value <= 2 ? dominantPool : secondaryPool));

    const currentQuestion = computed(() => partQuestions.value[instinctPoolIndices.value[currentInstinct.value]]);

    const maxAnswersPerQuestion = computed(() => currentConfig.value.answersPerQuestion);

    const dominantPool = shuffleByPriority(questions.filter((q) => q.id.startsWith(`${dominantInstinct}-`)));
    const secondaryPool = shuffleByPriority(questions.filter((q) => q.id.startsWith(`${secondaryInstinct}-`)));

    // --- Flat options with per-part filtering ---
    const flatOptions = computed<FlatOption[]>(() => {
        const q = currentQuestion.value;
        if (!q) return [];

        if (!shuffledAnswersPerQuestion.value[q.id]) {
            shuffledAnswersPerQuestion.value[q.id] = buildShuffledFlatOptions(q);
        }
        const flat = shuffledAnswersPerQuestion.value[q.id];

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

    // --- Submodules ---
    const {
        selectedAnswers,
        toggleAnswer,
        clear: clearSelection,
    } = useAnswerSelection({
        maxAnswers: maxAnswersPerQuestion,
        onAutoConfirm: () => confirmAnswers(),
    });

    const canSkip = computed(() => selectedAnswers.value.length === 0 && skips.value < currentConfig.value.maxSkips);

    const {
        history,
        recordAnswer,
        recordSkip,
        pop: popHistory,
    } = useHistory<Stage2Snapshot>(
        () => ({
            part: currentPart.value,
            index: currentIndex.value,
            typeScores: { ...typeScores.value },
            scoresPerPart: JSON.parse(JSON.stringify(scoresPerPart.value)),
            selectedInPart1: Array.from(selectedInPart1.value),
            selectedInPart3: Array.from(selectedInPart3.value),
            skips: skips.value,
            poolIndex: instinctPoolIndices.value[currentInstinct.value],
            instinct: currentInstinct.value,
        }),
        (s) => {
            currentPart.value = s.part;
            currentIndex.value = s.index;
            skips.value = s.skips;
            typeScores.value = { ...s.typeScores };
            scoresPerPart.value = JSON.parse(JSON.stringify(s.scoresPerPart));
            selectedInPart1.value = new Set(s.selectedInPart1);
            selectedInPart3.value = new Set(s.selectedInPart3);
            // Restore pool index using captured instinct to avoid reliance on currentInstinct order.
            instinctPoolIndices.value[s.instinct] = s.poolIndex;
        },
    );

    // --- Helpers ---
    function hasTieBreakingLead(scores: Record<EnneagramType, number>): boolean {
        const sorted = Object.values(scores).sort((a, b) => b - a);
        return sorted.length >= 2 && sorted[0] - sorted[1] >= 2;
    }

    function isCurrentPartTieBreaker(): boolean {
        return currentPart.value === 2 || currentPart.value === 4;
    }

    function shouldContinueForTieBreaking(): boolean {
        if (!isCurrentPartTieBreaker()) return false;
        const noMoreQuestions = instinctPoolIndices.value[currentInstinct.value] >= partQuestions.value.length;
        if (noMoreQuestions) return false;
        return !hasTieBreakingLead(typeScores.value);
    }

    function applyAnswersToScores(answers: SelectedAnswer[]) {
        for (const ans of answers) {
            const cat = String(ans.category) as EnneagramType;
            typeScores.value[cat] = (typeScores.value[cat] ?? 0) + 1;
            scoresPerPart.value[currentPart.value][cat] = (scoresPerPart.value[currentPart.value][cat] ?? 0) + 1;
            if (currentPart.value === 1) selectedInPart1.value.add(cat);
            if (currentPart.value === 3) selectedInPart3.value.add(cat);
        }
    }

    function shouldSkipPart(part: number): boolean {
        if (part === 2) return selectedInPart1.value.size <= 1;
        if (part === 4) return selectedInPart3.value.size <= 1;
        return false;
    }

    function moveToNextAvailablePart(): boolean {
        while (currentPart.value < LAST_PART) {
            currentPart.value++;

            if (!shouldSkipPart(currentPart.value)) {
                currentIndex.value = 0;
                skips.value = 0;
                return true;
            }
        }

        return false;
    }

    function isTiedAtTop(scores: Record<EnneagramType, number>): boolean {
        const sorted = Object.values(scores).sort((a, b) => b - a);
        return sorted.length >= 2 && sorted[0] === sorted[1];
    }

    function advance() {
        instinctPoolIndices.value[currentInstinct.value]++;
        currentIndex.value++;

        const reachedMax = currentIndex.value >= currentConfig.value.maxQuestions;
        const noMoreQuestions = instinctPoolIndices.value[currentInstinct.value] >= partQuestions.value.length;

        if (!(reachedMax || noMoreQuestions)) return;

        // For parts 2 and 4: keep asking if no type has +2 lead and pool not exhausted
        if (shouldContinueForTieBreaking()) return;

        if (moveToNextAvailablePart()) {
            return;
        }

        const isUnresolvable = isTiedAtTop(typeScores.value);
        emit?.('complete', { typeScores: { ...typeScores.value }, scoresPerPart: { ...scoresPerPart.value }, isUnresolvable });
    }

    // --- Actions ---
    function confirmAnswers() {
        if (selectedAnswers.value.length === 0 && !canSkip.value) return;

        recordAnswer(currentPart.value, selectedAnswers.value, skips.value);
        applyAnswersToScores(selectedAnswers.value);
        clearSelection();
        advance();
    }

    function handleSkip() {
        if (skips.value >= currentConfig.value.maxSkips) return;
        recordSkip(currentPart.value, skips.value);
        skips.value++;
        advance();
    }

    function goBack() {
        const last = popHistory();
        if (!last) return;
        selectedAnswers.value = last.type === 'answer' ? [...last.answers] : [];
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
        canSkip,
        toggleAnswer,
        confirmAnswers,
        handleSkip,
        goBack,
        currentInstinct,
        instinctPoolIndices,
    };
}

export type { FlatOption } from './shared/types';
