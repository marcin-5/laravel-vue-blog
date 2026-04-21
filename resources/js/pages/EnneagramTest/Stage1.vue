<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    questions: any[];
    config: any;
    debug?: boolean;
}>();

const currentPart = ref(1);
const currentIndex = ref(0);
const history = ref<any[]>([]); // To store history and allow back
const skips = ref(0);
const selectedAnswers = ref<any[]>([]);

// Separate scores for parts 1 and 2
const scoresPart1 = ref<Record<string, number>>({ sp: 0, sc: 0, sx: 0 });
const scoresPart2 = ref<Record<string, number>>({ sp: 0, sc: 0, sx: 0 });

const currentScores = computed(() => (currentPart.value === 1 ? scoresPart1.value : scoresPart2.value));

// Counters of answered questions (skips do not count)
const answeredCountPart1 = ref(0);
const answeredCountPart2 = ref(0);

// Winner from part 1 (SP/SO/SX) — used by the thresholdY rule in part 2
const part1Winner = ref<string | null>(null);

// One-time extra question flag in part 2 (tie-breaker)
const extraAskedPart2 = ref(false);

const currentConfig = computed(() => {
    return currentPart.value === 1 ? props.config.part1 : props.config.part2;
});

// Clarifying alias: treat this as the MAXIMUM number of answers per question
const maxAnswersPerQuestion = computed<number>(() => Number(currentConfig.value.answersPerQuestion ?? 1));

const formattedDesc = computed(() => {
    let desc = currentConfig.value.desc || '';
    // Descriptions use the %answersPerQuestion placeholder, which semantically means "at most"
    desc = desc.replace(/%answersPerQuestion/g, String(maxAnswersPerQuestion.value));
    desc = desc.replace(/%maxSkips/g, currentConfig.value.maxSkips);
    desc = desc.replace(/%fixedQuestions/g, currentConfig.value.fixedQuestions);
    return desc;
});

const partQuestions = computed(() => {
    return props.questions.filter((q) => q.part === currentPart.value);
});

const currentQuestion = computed(() => partQuestions.value[currentIndex.value]);

type FlatOption = { key: string; value: string; category: string };

// Cache for stable shuffling within the session (back/forward navigation)
const shuffledPerQuestion = ref<Record<string, FlatOption[]>>({});

function buildShuffledFlatOptions(q: any): FlatOption[] {
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
    // Fully shuffle all individual answers (Fisher–Yates)
    for (let i = flat.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [flat[i], flat[j]] = [flat[j], flat[i]];
    }
    return flat;
}

// Initialize shuffling for the current question when it changes
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

const flatShuffledOptions = computed<FlatOption[]>(() => {
    const q = currentQuestion.value;
    if (!q || !q.answerLists) {
        return [];
    }
    const questionId: string = String(q.id ?? `${q.stage}-${q.part}-${currentIndex.value}`);
    return shuffledPerQuestion.value[questionId] ?? [];
});

const isSelected = (key: string | number) => {
    return selectedAnswers.value.some((a) => String(a.key) === String(key));
};

const canSkip = computed(() => {
    return selectedAnswers.value.length === 0 && skips.value < currentConfig.value.maxSkips;
});

const toggleAnswer = (key: string | number, value: any, category?: string | number) => {
    const index = selectedAnswers.value.findIndex((a) => String(a.key) === String(key));

    if (index > -1) {
        selectedAnswers.value.splice(index, 1);
    } else {
        // allow selecting up to the maximum number of answers
        if (selectedAnswers.value.length < maxAnswersPerQuestion.value) {
            selectedAnswers.value.push({ key, value, category });
        } else if (maxAnswersPerQuestion.value === 1) {
            selectedAnswers.value = [{ key, value, category }];
        }
    }

    // auto-advance when at most 1 answer is allowed
    if (maxAnswersPerQuestion.value === 1 && selectedAnswers.value.length === 1) {
        setTimeout(confirmAnswers, 200);
    }
};

const confirmAnswers = () => {
    // Snapshot before change for history
    const prev = {
        scoresPart1: { ...scoresPart1.value },
        scoresPart2: { ...scoresPart2.value },
        answered1: answeredCountPart1.value,
        answered2: answeredCountPart2.value,
        part1Winner: part1Winner.value,
    };

    // Update scores for the current part
    const target = currentPart.value === 1 ? scoresPart1.value : scoresPart2.value;
    selectedAnswers.value.forEach((answer) => {
        const category = String(answer.category || answer.key);
        if (target[category] !== undefined) {
            target[category] += 1;
        }
    });

    // Increase answered counter (skip does not count — we have answers here)
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

    // Clear selections and run transition logic
    selectedAnswers.value = [];
    advanceFlowAfterAnswer();
};

// Move to the next question in the current part or switch/end according to the rules
const advanceFlowAfterAnswer = () => {
    if (currentPart.value === 1) {
        // thresholdX condition or reaching the limit of answered questions
        const thX: number = Number(props.config.part1.thresholdX ?? 0);
        const reachedLead = hasLead(scoresPart1.value, thX);
        const reachedMax = answeredCountPart1.value >= Number(props.config.part1.maxQuestions ?? 0);

        if (reachedLead || reachedMax || currentIndex.value >= partQuestions.value.length - 1) {
            // Determine part 1 leader
            part1Winner.value = getLeader(scoresPart1.value);
            // Move to part 2
            currentPart.value = 2;
            currentIndex.value = 0;
            skips.value = 0;
            selectedAnswers.value = [];
            extraAskedPart2.value = false;
            return;
        }

        // Otherwise move to the next question in this part
        currentIndex.value = Math.min(currentIndex.value + 1, partQuestions.value.length - 1);
        return;
    }

    // Part 2 — check thresholdX (only scores from part 2)
    const thX2: number = Number(props.config.part2.thresholdX ?? 0);
    const endByX = hasLead(scoresPart2.value, thX2);

    // thresholdY: only if the winner from part 1 has not been chosen at all in part 2
    const thY: number = Number(props.config.part2.thresholdY ?? 0);
    const specialYApplicable = part1Winner.value != null && (scoresPart2.value[part1Winner.value] ?? 0) === 0;
    const endByY = specialYApplicable && hasLead(scoresPart2.value, thY);

    const reachedMax2 = answeredCountPart2.value >= Number(props.config.part2.maxQuestions ?? 0);

    // Tie for 1st place after reaching the question limit — show one extra question (if available)
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
        // End of stage 1 — show stats from both parts
        const statsP1 = `P1 -> SP:${scoresPart1.value.sp}, SC:${scoresPart1.value.so}, SX:${scoresPart1.value.sx}`;
        const statsP2 = `P2 -> SP:${scoresPart2.value.sp}, SC:${scoresPart2.value.so}, SX:${scoresPart2.value.sx}`;
        alert(`Etap 1 zakończony\n\n${statsP1}\n${statsP2}`);
        return;
    }

    // Otherwise next question of part 2
    currentIndex.value = Math.min(currentIndex.value + 1, partQuestions.value.length - 1);
};

// Checks if the leader's advantage over second place is at least threshold
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

// Is there a tie for first place (at least two highest scores are equal)
function isTopTwoTie(scores: Record<string, number>): boolean {
    const values = [scores.sp ?? 0, scores.so ?? 0, scores.sx ?? 0].sort((a, b) => b - a);
    if (values.length < 2) {
        return false;
    }
    return values[0] === values[1];
}

// Returns the leader key (SP/SO/SX) — on tie uses preferred order
function getLeader(scores: Record<string, number>): string {
    const order = ['sp', 'sc', 'sx'];
    let best = order[0];
    for (const k of order) {
        if ((scores[k] ?? 0) > (scores[best] ?? 0)) {
            best = k;
        }
    }
    return best;
}

const handleSkip = () => {
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

        // Move to the next question within the current part (skip does not end the part by itself)
        if (currentIndex.value < partQuestions.value.length - 1) {
            currentIndex.value++;
        } else {
            // if questions are exhausted — move to the next part or finish (according to the rules)
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
};

const goBack = () => {
    if (history.value.length > 0) {
        const lastStep: any = history.value.pop();
        currentPart.value = lastStep.part;
        currentIndex.value = lastStep.index;
        skips.value = lastStep.skipsAtThisPoint;

        // Restore full snapshot
        if (lastStep.snapshot) {
            scoresPart1.value = { ...lastStep.snapshot.scoresPart1 };
            scoresPart2.value = { ...lastStep.snapshot.scoresPart2 };
            answeredCountPart1.value = lastStep.snapshot.answered1;
            answeredCountPart2.value = lastStep.snapshot.answered2;
            part1Winner.value = lastStep.snapshot.part1Winner;
            extraAskedPart2.value = !!lastStep.snapshot.extraAskedPart2;
        }

        if (lastStep.type === 'answer') {
            selectedAnswers.value = lastStep.answers;
        } else {
            selectedAnswers.value = [];
        }
    }
};

// Reset selected answers when question changes (should be handled by goBack/nextQuestion, but as a safeguard)
watch([currentPart, currentIndex], () => {
    // If we're moving forward and not through goBack, selectedAnswers should be empty.
    // However, goBack sets selectedAnswers before updating indices, so we need to be careful.
});
</script>
<template>
    <div class="mx-auto max-w-2xl p-6">
        <h2 class="mb-4 text-xl font-bold text-foreground">Etap 1: Część {{ currentPart }}</h2>
        <p class="mb-6 text-muted-foreground">{{ formattedDesc }}</p>

        <Card v-if="currentQuestion">
            <CardHeader>
                <CardTitle class="font-quicksand text-lg text-pretty break-words">{{ currentQuestion.question }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <Button
                        v-for="opt in flatShuffledOptions"
                        :key="opt.key"
                        :variant="isSelected(opt.key) ? 'secondary' : 'outline'"
                        class="w-full justify-start py-6 text-left font-recursive text-pretty break-words whitespace-normal"
                        size="lg"
                        @click="toggleAnswer(opt.key, opt.value, opt.category)"
                    >
                        {{ opt.value }}
                    </Button>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <div class="flex gap-2">
                        <Button :disabled="history.length === 0" variant="outline" @click="goBack"> Wstecz </Button>
                        <Button v-if="canSkip" :disabled="skips >= currentConfig.maxSkips" variant="muted" @click="handleSkip">
                            Pomiń ({{ skips }}/{{ currentConfig.maxSkips }})
                        </Button>
                    </div>

                    <Button
                        v-if="maxAnswersPerQuestion > 1"
                        :disabled="selectedAnswers.length < 1"
                        class="px-6"
                        variant="secondary"
                        @click="confirmAnswers"
                    >
                        Dalej
                    </Button>
                </div>

                <div v-if="debug" class="mt-6 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
                    <div class="mb-2 font-semibold text-foreground">DEBUG: Statystyki (Etap 1)</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            Part: <span class="text-foreground">{{ currentPart }}</span>
                        </div>
                        <div>
                            Pytanie: <span class="text-foreground">{{ currentIndex + 1 }}/{{ partQuestions.length }}</span>
                        </div>
                        <div>
                            Pominięcia: <span class="text-foreground">{{ skips }}/{{ currentConfig.maxSkips }}</span>
                        </div>
                        <div>
                            Wybrane: <span class="text-foreground">{{ selectedAnswers.length }}/{{ maxAnswersPerQuestion }}</span>
                        </div>
                        <div>
                            Odpowiedziane (P1/P2):
                            <span class="text-foreground"
                                >{{ answeredCountPart1 }}/{{ config.part1.maxQuestions }} / {{ answeredCountPart2 }}/{{
                                    config.part2.maxQuestions
                                }}</span
                            >
                        </div>
                    </div>
                    <div class="mt-2">
                        Wyniki bieżącej części:
                        <span class="text-foreground">SP={{ currentScores.sp }}, SC={{ currentScores.so }}, SX={{ currentScores.sx }}</span>
                    </div>
                    <div class="mt-2">
                        Wynik części 1 (lider): <span class="text-foreground">{{ part1Winner || '-' }}</span>
                    </div>
                    <div class="mt-2">
                        Historia kroków: <span class="text-foreground">{{ history.length }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
