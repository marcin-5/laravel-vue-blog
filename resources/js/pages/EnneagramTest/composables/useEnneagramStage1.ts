import { computed, ref, watch } from 'vue';

export interface Question {
    id?: string | number;
    part: number;
    question: string;
    answerLists: Record<string, string | string[]>;
    stage?: number;
}

export interface Config {
    part1: PartConfig;
    part2: PartConfig;
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

export interface FlatOption {
    key: string;
    value: string;
    category: string;
}

export interface HistoryItem {
    part: number;
    index: number;
    answers?: any[];
    type: 'answer' | 'skip';
    skipsAtThisPoint: number;
    snapshot: any;
}

export function useEnneagramStage1(questions: Question[], config: Config) {
    const currentPart = ref(1);
    const currentIndex = ref(0);
    const history = ref<HistoryItem[]>([]);
    const skips = ref(0);
    const selectedAnswers = ref<any[]>([]);

    const scoresPart1 = ref<Record<string, number>>({ sp: 0, so: 0, sx: 0 });
    const scoresPart2 = ref<Record<string, number>>({ sp: 0, so: 0, sx: 0 });

    const answeredCountPart1 = ref(0);
    const answeredCountPart2 = ref(0);
    const part1Winner = ref<string | null>(null);
    const extraAskedPart2 = ref(false);

    const shuffledPerQuestion = ref<Record<string, FlatOption[]>>({});

    // Computed
    const currentScores = computed(() => (currentPart.value === 1 ? scoresPart1.value : scoresPart2.value));

    const currentConfig = computed(() => {
        return currentPart.value === 1 ? config.part1 : config.part2;
    });

    const maxAnswersPerQuestion = computed<number>(() => Number(currentConfig.value.answersPerQuestion ?? 1));

    const formattedDesc = computed(() => {
        let desc = currentConfig.value.desc || '';
        desc = desc.replace(/%answersPerQuestion/g, String(maxAnswersPerQuestion.value));
        desc = desc.replace(/%maxSkips/g, String(currentConfig.value.maxSkips));
        desc = desc.replace(/%fixedQuestions/g, String(currentConfig.value.fixedQuestions));
        return desc;
    });

    const partQuestions = computed(() => {
        return questions.filter((q) => q.part === currentPart.value);
    });

    const currentQuestion = computed(() => partQuestions.value[currentIndex.value]);

    const flatShuffledOptions = computed<FlatOption[]>(() => {
        const q = currentQuestion.value;
        if (!q || !q.answerLists) {
            return [];
        }
        const questionId: string = String(q.id ?? `${q.stage}-${q.part}-${currentIndex.value}`);
        return shuffledPerQuestion.value[questionId] ?? [];
    });

    const canSkip = computed(() => {
        return selectedAnswers.value.length === 0 && skips.value < currentConfig.value.maxSkips;
    });

    // Helper functions
    function buildShuffledFlatOptions(q: Question): FlatOption[] {
        const flat: FlatOption[] = [];
        Object.entries(q.answerLists).forEach(([category, options]) => {
            if (Array.isArray(options)) {
                options.forEach((option, idx) => {
                    flat.push({ key: `${category}-${idx}`, value: String(option), category });
                });
            } else {
                flat.push({ key: String(category), value: String(options), category: String(category) });
            }
        });

        for (let i = flat.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [flat[i], flat[j]] = [flat[j], flat[i]];
        }
        return flat;
    }

    function isSelected(key: string | number) {
        return selectedAnswers.value.some((a) => String(a.key) === String(key));
    }

    function hasLead(scores: Record<string, number>, threshold: number): boolean {
        if (!threshold || threshold <= 0) {
            return false;
        }
        const values = [scores.sp ?? 0, scores.so ?? 0, scores.sx ?? 0].sort((a, b) => b - a);
        if (values.length < 2) {
            return false;
        }
        return values[0] - values[1] >= threshold;
    }

    function isTopTwoTie(scores: Record<string, number>): boolean {
        const values = [scores.sp ?? 0, scores.so ?? 0, scores.sx ?? 0].sort((a, b) => b - a);
        if (values.length < 2) {
            return false;
        }
        return values[0] === values[1];
    }

    function getLeader(scores: Record<string, number>): string {
        const order = ['sp', 'so', 'sx'];
        let best = order[0];
        for (const k of order) {
            if ((scores[k] ?? 0) > (scores[best] ?? 0)) {
                best = k;
            }
        }
        return best;
    }

    // Actions
    function toggleAnswer(key: string | number, value: any, category?: string | number) {
        const index = selectedAnswers.value.findIndex((a) => String(a.key) === String(key));

        if (index > -1) {
            selectedAnswers.value.splice(index, 1);
        } else {
            if (selectedAnswers.value.length < maxAnswersPerQuestion.value) {
                selectedAnswers.value.push({ key, value, category });
            } else if (maxAnswersPerQuestion.value === 1) {
                selectedAnswers.value = [{ key, value, category }];
            }
        }

        if (maxAnswersPerQuestion.value === 1 && selectedAnswers.value.length === 1) {
            setTimeout(confirmAnswers, 200);
        }
    }

    function confirmAnswers() {
        const prev = {
            scoresPart1: { ...scoresPart1.value },
            scoresPart2: { ...scoresPart2.value },
            answered1: answeredCountPart1.value,
            answered2: answeredCountPart2.value,
            part1Winner: part1Winner.value,
        };

        const target = currentPart.value === 1 ? scoresPart1.value : scoresPart2.value;
        selectedAnswers.value.forEach((answer) => {
            const category = String(answer.category || answer.key);
            if (target[category] !== undefined) {
                target[category] += 1;
            }
        });

        if (currentPart.value === 1) {
            answeredCountPart1.value += 1;
        } else {
            answeredCountPart2.value += 1;
        }

        history.value.push({
            part: currentPart.value,
            index: currentIndex.value,
            answers: [...selectedAnswers.value],
            type: 'answer',
            skipsAtThisPoint: skips.value,
            snapshot: prev,
        });

        selectedAnswers.value = [];
        advanceFlowAfterAnswer();
    }

    function advanceFlowAfterAnswer() {
        if (currentPart.value === 1) {
            const thX: number = Number(config.part1.thresholdX ?? 0);
            const reachedLead = hasLead(scoresPart1.value, thX);
            const reachedMax = answeredCountPart1.value >= Number(config.part1.maxQuestions ?? 0);

            if (reachedLead || reachedMax || currentIndex.value >= partQuestions.value.length - 1) {
                part1Winner.value = getLeader(scoresPart1.value);
                currentPart.value = 2;
                currentIndex.value = 0;
                skips.value = 0;
                selectedAnswers.value = [];
                extraAskedPart2.value = false;
                return;
            }

            currentIndex.value = Math.min(currentIndex.value + 1, partQuestions.value.length - 1);
            return;
        }

        const thX2: number = Number(config.part2.thresholdX ?? 0);
        const endByX = hasLead(scoresPart2.value, thX2);

        const thY: number = Number(config.part2.thresholdY ?? 0);
        const specialYApplicable = part1Winner.value != null && (scoresPart2.value[part1Winner.value] ?? 0) === 0;
        const endByY = specialYApplicable && hasLead(scoresPart2.value, thY);

        const reachedMax2 = answeredCountPart2.value >= Number(config.part2.maxQuestions ?? 0);

        if (
            !endByX &&
            !endByY &&
            reachedMax2 &&
            !extraAskedPart2.value &&
            isTopTwoTie(scoresPart2.value) &&
            currentIndex.value < partQuestions.value.length - 1
        ) {
            extraAskedPart2.value = true;
            currentIndex.value = Math.min(currentIndex.value + 1, partQuestions.value.length - 1);
            return;
        }

        if (endByX || endByY || reachedMax2 || currentIndex.value >= partQuestions.value.length - 1) {
            const statsP1 = `P1 -> SP:${scoresPart1.value.sp}, SC:${scoresPart1.value.so}, SX:${scoresPart1.value.sx}`;
            const statsP2 = `P2 -> SP:${scoresPart2.value.sp}, SC:${scoresPart2.value.so}, SX:${scoresPart2.value.sx}`;
            alert(`Etap 1 zakończony\n\n${statsP1}\n${statsP2}`);
            return;
        }

        currentIndex.value = Math.min(currentIndex.value + 1, partQuestions.value.length - 1);
    }

    function handleSkip() {
        if (skips.value < currentConfig.value.maxSkips) {
            const snapshot = {
                scoresPart1: { ...scoresPart1.value },
                scoresPart2: { ...scoresPart2.value },
                answered1: answeredCountPart1.value,
                answered2: answeredCountPart2.value,
                part1Winner: part1Winner.value,
                extraAskedPart2: extraAskedPart2.value,
            };
            history.value.push({
                part: currentPart.value,
                index: currentIndex.value,
                type: 'skip',
                skipsAtThisPoint: skips.value,
                snapshot,
            });
            skips.value++;

            if (currentIndex.value < partQuestions.value.length - 1) {
                currentIndex.value++;
            } else {
                if (currentPart.value === 1) {
                    part1Winner.value = getLeader(scoresPart1.value);
                    currentPart.value = 2;
                    currentIndex.value = 0;
                    skips.value = 0;
                    selectedAnswers.value = [];
                } else {
                    const statsP1 = `P1 -> SP:${scoresPart1.value.sp}, SC:${scoresPart1.value.so}, SX:${scoresPart1.value.sx}`;
                    const statsP2 = `P2 -> SP:${scoresPart2.value.sp}, SC:${scoresPart2.value.so}, SX:${scoresPart2.value.sx}`;
                    alert(`Etap 1 zakończony (koniec pytań)\n\n${statsP1}\n${statsP2}`);
                }
            }
        }
    }

    function goBack() {
        if (history.value.length > 0) {
            const lastStep = history.value.pop();
            if (!lastStep) return;

            currentPart.value = lastStep.part;
            currentIndex.value = lastStep.index;
            skips.value = lastStep.skipsAtThisPoint;

            if (lastStep.snapshot) {
                scoresPart1.value = { ...lastStep.snapshot.scoresPart1 };
                scoresPart2.value = { ...lastStep.snapshot.scoresPart2 };
                answeredCountPart1.value = lastStep.snapshot.answered1;
                answeredCountPart2.value = lastStep.snapshot.answered2;
                part1Winner.value = lastStep.snapshot.part1Winner;
                extraAskedPart2.value = !!lastStep.snapshot.extraAskedPart2;
            }

            if (lastStep.type === 'answer') {
                selectedAnswers.value = lastStep.answers || [];
            } else {
                selectedAnswers.value = [];
            }
        }
    }

    // Watchers
    watch(
        [currentPart, currentIndex],
        () => {
            const q = currentQuestion.value;
            if (!q || !q.answerLists) {
                return;
            }
            const questionId: string = String(q.id ?? `${q.stage}-${q.part}-${currentIndex.value}`);
            if (!shuffledPerQuestion.value[questionId]) {
                shuffledPerQuestion.value[questionId] = buildShuffledFlatOptions(q);
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
