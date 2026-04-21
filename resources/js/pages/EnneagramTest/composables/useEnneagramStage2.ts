import { computed, ref } from 'vue';

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

export interface FlatOption {
    key: string;
    value: string;
    category: string;
}

export interface HistoryItem {
    part: number;
    questionId: string;
    answers: any[];
    type: 'answer' | 'skip';
    skipsAtThisPoint: number;
    snapshot: any;
}

export function useEnneagramStage2(questions: Question[], config: Config, resultsStage1: { dominant: string; secondary: string }) {
    const currentPart = ref(1);
    const currentIndex = ref(0);
    const history = ref<HistoryItem[]>([]);
    const skips = ref(0);
    const selectedAnswers = ref<any[]>([]);

    // Points for types 1-9
    const typeScores = ref<Record<string, number>>({
        '1': 0,
        '2': 0,
        '3': 0,
        '4': 0,
        '5': 0,
        '6': 0,
        '7': 0,
        '8': 0,
        '9': 0,
    });

    // Answers selected in part 1 (to filter in part 2)
    const selectedInPart1 = ref<Set<string>>(new Set());
    // Answers selected in part 3 (to filter in part 4)
    const selectedInPart3 = ref<Set<string>>(new Set());

    const shuffledAnswersPerQuestion = ref<Record<string, FlatOption[]>>({});

    // Determine current instinct based on part
    const currentInstinct = computed(() => {
        if (!resultsStage1 || currentPart.value <= 2) return resultsStage1?.dominant || 'sp';
        return resultsStage1?.secondary || 'so';
    });

    // Weighted shuffle logic
    function shuffleByPriority(instinct: string): Question[] {
        const pool = questions.filter((q) => q.id.startsWith(`${instinct}-`));
        const weightedPool: string[] = [];

        pool.forEach((q) => {
            const weight = q.priority + 1; // 0->1, 1->2, 2->3 (as per req, though json has 0)
            for (let i = 0; i < weight; i++) {
                weightedPool.push(q.id);
            }
        });

        // Shuffle weighted pool
        for (let i = weightedPool.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [weightedPool[i], weightedPool[j]] = [weightedPool[j], weightedPool[i]];
        }

        // Keep first occurrence to maintain uniqueness while respecting weighted shuffle
        const uniqueIds = Array.from(new Set(weightedPool));
        return uniqueIds.map((id) => pool.find((q) => q.id === id)!);
    }

    // Initialize pools
    const dominantPool = shuffleByPriority(resultsStage1?.dominant || 'sp');
    const secondaryPool = shuffleByPriority(resultsStage1?.secondary || 'so');

    const partQuestions = computed(() => {
        if (currentPart.value <= 2) return dominantPool;
        return secondaryPool;
    });

    const currentConfig = computed(() => {
        const key = `part${currentPart.value}` as keyof Config;
        return config[key];
    });

    // Global index for each instinct pool to avoid repeating questions across parts
    const instinctPoolIndices = ref<Record<string, number>>({
        [resultsStage1?.dominant || 'sp']: 0,
        [resultsStage1?.secondary || 'so']: 0,
    });

    const currentQuestion = computed(() => {
        const instinct = currentInstinct.value;
        const indexInPool = instinctPoolIndices.value[instinct];
        return partQuestions.value[indexInPool];
    });

    function buildShuffledFlatOptions(q: Question): FlatOption[] {
        const flat: FlatOption[] = [];
        Object.entries(q.answerLists).forEach(([category, value]) => {
            flat.push({
                key: category,
                value: Array.isArray(value) ? value[0] : String(value),
                category,
            });
        });

        for (let i = flat.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [flat[i], flat[j]] = [flat[j], flat[i]];
        }
        return flat;
    }

    const flatOptions = computed<FlatOption[]>(() => {
        const q = currentQuestion.value;
        if (!q) return [];

        const questionId = q.id;
        if (!shuffledAnswersPerQuestion.value[questionId]) {
            shuffledAnswersPerQuestion.value[questionId] = buildShuffledFlatOptions(q);
        }

        const flat = shuffledAnswersPerQuestion.value[questionId];

        // Filter options for parts 2 and 4
        if (currentPart.value === 2) {
            return flat.filter((opt) => selectedInPart1.value.has(opt.category));
        }
        if (currentPart.value === 4) {
            // "te, które chociaż raz zostały wskazane w części pierwszej i trzeciej"
            // Actually description says: "tylko te odpowiedzi, które chociaż raz zostały wskazane w części pierwszej" for part 2
            // and "analogicznie w części 3 i 4" - so for 4 we use results from 3?
            // WAIT: User said: "4 (wyświetlane tylko te, które chociaż raz były wskazane w części 1 i 3)"
            // Let's re-read carefully: "wyświetlamy tylko te odpowiedzi, które chociaż raz zostały wskazane w części pierwszej" (for part 2)
            // "Analogicznie postępujemy w części 3 i 4... używamy zestawu sx-"
            // Then later: "4 (wyświetlane tylko te, które chociaż raz były wskazane w części 1 i 3)"
            // OK, so Part 4 = filter by (Selected in Part 1 OR Selected in Part 3)
            return flat.filter((opt) => selectedInPart1.value.has(opt.category) || selectedInPart3.value.has(opt.category));
        }

        return flat;
    });

    const maxAnswersPerQuestion = computed(() => currentConfig.value.answersPerQuestion);

    const canSkip = computed(() => {
        return selectedAnswers.value.length === 0 && skips.value < currentConfig.value.maxSkips;
    });

    function toggleAnswer(key: string | number, value: any, category?: string | number) {
        const idx = selectedAnswers.value.findIndex((a) => String(a.key) === String(key));
        if (idx > -1) {
            selectedAnswers.value.splice(idx, 1);
        } else {
            if (selectedAnswers.value.length < maxAnswersPerQuestion.value) {
                selectedAnswers.value.push({ key, value, category: category ?? '' });
            } else if (maxAnswersPerQuestion.value === 1) {
                selectedAnswers.value = [{ key, value, category: category ?? '' }];
            }
        }

        // If only 1 answer is required, confirm immediately after selecting
        if (maxAnswersPerQuestion.value === 1 && selectedAnswers.value.length === 1) {
            confirmAnswers();
        }
    }

    function confirmAnswers() {
        if (selectedAnswers.value.length === 0 && !canSkip.value) return;

        const prev = {
            typeScores: { ...typeScores.value },
            selectedInPart1: new Set(selectedInPart1.value),
            selectedInPart3: new Set(selectedInPart3.value),
            skips: skips.value,
            currentIndex: currentIndex.value,
            currentPart: currentPart.value,
        };

        history.value.push({
            part: currentPart.value,
            questionId: currentQuestion.value?.id,
            answers: [...selectedAnswers.value],
            type: 'answer',
            skipsAtThisPoint: skips.value,
            snapshot: {
                ...prev,
                poolIndex: instinctPoolIndices.value[currentInstinct.value],
            },
        });

        // Add to scores and selected trackers
        selectedAnswers.value.forEach((ans) => {
            const cat = String(ans.category);
            typeScores.value[cat] = (typeScores.value[cat] || 0) + 1;
            if (currentPart.value === 1) selectedInPart1.value.add(cat);
            if (currentPart.value === 3) selectedInPart3.value.add(cat);
        });

        selectedAnswers.value = [];
        advance();
    }

    function handleSkip() {
        if (skips.value < currentConfig.value.maxSkips) {
            const prev = {
                typeScores: { ...typeScores.value },
                selectedInPart1: new Set(selectedInPart1.value),
                selectedInPart3: new Set(selectedInPart3.value),
                skips: skips.value,
                currentIndex: currentIndex.value,
                currentPart: currentPart.value,
            };
            history.value.push({
                part: currentPart.value,
                questionId: currentQuestion.value?.id,
                answers: [],
                type: 'skip',
                skipsAtThisPoint: skips.value,
                snapshot: {
                    ...prev,
                    poolIndex: instinctPoolIndices.value[currentInstinct.value],
                },
            });
            skips.value++;
            advance();
        }
    }

    function advance() {
        const instinct = currentInstinct.value;
        instinctPoolIndices.value[instinct]++;
        currentIndex.value++;

        const reachedMax = currentIndex.value >= currentConfig.value.maxQuestions;
        const noMoreQuestions = instinctPoolIndices.value[instinct] >= partQuestions.value.length;

        if (reachedMax || noMoreQuestions) {
            if (currentPart.value < 4) {
                currentPart.value++;
                currentIndex.value = 0;
                skips.value = 0;
            } else {
                alert('Test zakończony! Wyniki: ' + JSON.stringify(typeScores.value));
            }
        }
    }

    function goBack() {
        if (history.value.length === 0) return;
        const last = history.value.pop()!;

        currentPart.value = last.part;
        currentIndex.value = last.snapshot.currentIndex;
        skips.value = last.skipsAtThisPoint;
        typeScores.value = { ...last.snapshot.typeScores };
        selectedInPart1.value = new Set(last.snapshot.selectedInPart1);
        selectedInPart3.value = new Set(last.snapshot.selectedInPart3);
        selectedAnswers.value = last.type === 'answer' ? [...last.answers] : [];

        // Restore global pool index
        instinctPoolIndices.value[currentInstinct.value] = last.snapshot.poolIndex;
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
