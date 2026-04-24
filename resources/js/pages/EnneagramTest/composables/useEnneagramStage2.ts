import { computed, ref } from 'vue';
import { type EnneagramType, TYPE_IDS } from './shared/constants';
import { buildShuffledFlatOptions, shuffleByPriority } from './shared/shuffle';
import type { FlatOption, Instinct, SelectedAnswer } from './shared/types';
import { useAnswerSelection } from './shared/useAnswerSelection';
import { useHistory } from './shared/useHistory';

export interface Question {
    id: string;
    stage: number;
    part: number;
    priority: number;
    question: string;
    answerLists: Record<string, string | string[]>;
}

export interface PartConfig {
    maxQuestions: number;
    maxSkips: number;
    answersPerQuestion: number;
    desc: string;
}

export interface Config {
    part1: PartConfig;
    part2: PartConfig;
    part3: PartConfig;
    part4: PartConfig;
}

export interface Stage1Results {
    dominant: Instinct;
    secondary: Instinct;
}

interface Stage2Snapshot {
    part: number;
    index: number;
    typeScores: Record<EnneagramType, number>;
    selectedInPart1: string[]; // Sets are serialized as arrays for snapshot simplicity
    selectedInPart3: string[];
    skips: number;
    poolIndex: number;
    instinct: Instinct;
}

type Stage2Emit = (event: 'complete', results: Record<EnneagramType, number>) => void;

const DEFAULT_DOMINANT: Instinct = 'sp';
const DEFAULT_SECONDARY: Instinct = 'so';
const LAST_PART = 4;

function createEmptyTypeScores(): Record<EnneagramType, number> {
    return TYPE_IDS.reduce((acc, id) => ({ ...acc, [id]: 0 }), {} as Record<EnneagramType, number>);
}

export function useEnneagramStage2(questions: Question[], config: Config, resultsStage1: Stage1Results, emit?: Stage2Emit) {
    const dominantInstinct: Instinct = resultsStage1?.dominant ?? DEFAULT_DOMINANT;
    const secondaryInstinct: Instinct = resultsStage1?.secondary ?? DEFAULT_SECONDARY;

    // --- State ---
    const currentPart = ref(1);
    const currentIndex = ref(0);
    const skips = ref(0);
    const typeScores = ref<Record<EnneagramType, number>>(createEmptyTypeScores());
    const selectedInPart1 = ref<Set<string>>(new Set());
    const selectedInPart3 = ref<Set<string>>(new Set());
    const shuffledAnswersPerQuestion = ref<Record<string, FlatOption[]>>({});

    const instinctPoolIndices = ref<Record<string, number>>({
        [dominantInstinct]: 0,
        [secondaryInstinct]: 0,
    });

    // --- Computed ---
    const currentInstinct = computed<Instinct>(() => (currentPart.value <= 2 ? dominantInstinct : secondaryInstinct));

    const currentConfig = computed(() => config[`part${currentPart.value}` as keyof Config]);

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
            selectedInPart1.value = new Set(s.selectedInPart1);
            selectedInPart3.value = new Set(s.selectedInPart3);
            // Restore pool index using captured instinct to avoid reliance on currentInstinct order.
            instinctPoolIndices.value[s.instinct] = s.poolIndex;
        },
    );

    // --- Helpers ---
    function applyAnswersToScores(answers: SelectedAnswer[]) {
        for (const ans of answers) {
            const cat = String(ans.category) as EnneagramType;
            typeScores.value[cat] = (typeScores.value[cat] ?? 0) + 1;
            if (currentPart.value === 1) selectedInPart1.value.add(cat);
            if (currentPart.value === 3) selectedInPart3.value.add(cat);
        }
    }

    function advance() {
        instinctPoolIndices.value[currentInstinct.value]++;
        currentIndex.value++;

        const reachedMax = currentIndex.value >= currentConfig.value.maxQuestions;
        const noMoreQuestions = instinctPoolIndices.value[currentInstinct.value] >= partQuestions.value.length;

        if (!(reachedMax || noMoreQuestions)) return;

        if (currentPart.value < LAST_PART) {
            currentPart.value++;
            currentIndex.value = 0;
            skips.value = 0;
        } else {
            emit?.('complete', { ...typeScores.value });
        }
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
