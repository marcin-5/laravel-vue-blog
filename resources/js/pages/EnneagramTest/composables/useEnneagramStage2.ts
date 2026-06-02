import { computed, ComputedRef, ref, type Ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { type EnneagramType, TYPE_IDS } from './shared/constants';
import { hasLead as hasScoringLead, isTopTwoTie as isScoringTiedAtTop } from './shared/scoring';
import { buildShuffledFlatOptions, shuffleByPriority } from './shared/shuffle';
import type {
    CompleteStage1Results,
    Config,
    FlatOption,
    Instinct,
    PartConfig,
    Question,
    SelectedAnswer,
    Stage2Results
} from './shared/types';
import { useBaseEnneagramStage } from './shared/useBaseEnneagramStage';

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
    bonusPointsPerPart: Record<number, number>;
}

type Stage2Emit = (event: 'complete', results: Stage2Results) => void;

const DEFAULT_DOMINANT: Instinct = 'sp';
const DEFAULT_SECONDARY: Instinct = 'so';
const LAST_PART = 4;

function createEmptyTypeScores(): Record<EnneagramType, number> {
    return TYPE_IDS.reduce((acc, id) => ({ ...acc, [id]: 0 }), {} as Record<EnneagramType, number>);
}

function cloneScoresPerPart(scores: Record<number, Record<EnneagramType, number>>): Record<number, Record<EnneagramType, number>> {
    return Object.fromEntries(Object.entries(scores).map(([part, partScores]) => [Number(part), { ...partScores }])) as Record<
        number,
        Record<EnneagramType, number>
    >;
}

function countDuplicateCategories(answers: SelectedAnswer[]): number {
    const categories = answers.map((answer) => String(answer.category || answer.key));
    const uniqueCategories = new Set(categories);

    return answers.length - uniqueCategories.size;
}

export function useEnneagramStage2(
    questions: Question[],
    config: Config['stages']['stage2'],
    resultsStage1: CompleteStage1Results,
    emit?: Stage2Emit,
    enableAutoConfirmSingle?: Ref<boolean>,
) {
    const dominantInstinct: Instinct = resultsStage1.isUnresolvable ? DEFAULT_DOMINANT : (resultsStage1.dominant ?? DEFAULT_DOMINANT);
    const secondaryInstinct: Instinct = resultsStage1.isUnresolvable ? DEFAULT_SECONDARY : (resultsStage1.secondary ?? DEFAULT_SECONDARY);

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
        [dominantInstinct]: 0,
        [secondaryInstinct]: 0,
    });

    const dominantPool = shuffleByPriority(questions.filter((q) => q.id.startsWith(`${dominantInstinct}-`)));
    const secondaryPool = shuffleByPriority(questions.filter((q) => q.id.startsWith(`${secondaryInstinct}-`)));

    // --- Helpers ---
    const getInstinct = (part: number) => (part <= 2 ? dominantInstinct : secondaryInstinct);
    const getPartQuestions = (part: number) => (part <= 2 ? dominantPool : secondaryPool);

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
        for (const ans of answers) {
            const cat = String(ans.category) as EnneagramType;
            typeScores.value[cat] = (typeScores.value[cat] ?? 0) + 1;
            scoresPerPart.value[part][cat] = (scoresPerPart.value[part][cat] ?? 0) + 1;
            if (part === 1) selectedInPart1.value.add(cat);
            if (part === 3) selectedInPart3.value.add(cat);
        }
    }

    function shouldSkipPart(part: number): boolean {
        if (part === 2) return selectedInPart1.value.size <= 1;
        if (part === 4) return selectedInPart3.value.size <= 1;
        return false;
    }

    function moveToNextAvailablePart(state: { currentPart: Ref<number>; skips: Ref<number> }): boolean {
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

    function advance(state: { currentPart: Ref<number>; skips: Ref<number>; currentConfig: ComputedRef<PartConfig> }, isAnswer = true) {
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

        const isUnresolvable = isScoringTiedAtTop(typeScores.value);
        emit?.('complete', { typeScores: { ...typeScores.value }, scoresPerPart: { ...scoresPerPart.value }, isUnresolvable });
    }

    // --- Base Composable ---
    const base = useBaseEnneagramStage<Stage2Snapshot>((state) => ({
        getPartConfig: (part) => config[`part${part}` as keyof typeof config],
        createSnapshot: () => {
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
        },
        restoreSnapshot: (s) => {
            state.currentPart.value = s.part;
            currentIndex.value = s.index;
            state.skips.value = s.skips;
            typeScores.value = { ...s.typeScores };
            scoresPerPart.value = cloneScoresPerPart(s.scoresPerPart);
            selectedInPart1.value = new Set(s.selectedInPart1);
            selectedInPart3.value = new Set(s.selectedInPart3);
            bonusPointsPerPart.value = { ...s.bonusPointsPerPart };
            instinctPoolIndices.value[s.instinct] = s.poolIndex;
        },
        onConfirm: (answers: SelectedAnswer[]) => {
            bonusPointsPerPart.value[state.currentPart.value] += countDuplicateCategories(answers);

            applyAnswersToScores(answers, state.currentPart.value);
        },
        onAdvance: (isAnswer: boolean) => {
            advance(state, isAnswer);
        },
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
        const scores = scoresPerPart.value[currentPart.value];
        const config = currentConfig.value;
        const sorted = (Object.entries(scores) as [EnneagramType, number][])
            .map(([key, score]) => ({ key, score }))
            .sort((a, b) => b.score - a.score);

        const leader = sorted[0];
        const second = sorted[1];
        const third = sorted[2];

        const results = [];
        const requiredLead = config.minLead ?? 2;

        results.push({
            label: t('lead_leader_vs_second'),
            current: leader.score - second.score,
            target: requiredLead,
            color: 'bg-secondary-foreground',
        });

        results.push({
            label: t('lead_second_vs_third'),
            current: second.score - third.score,
            target: requiredLead,
            color: 'bg-foreground',
        });

        return results;
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
