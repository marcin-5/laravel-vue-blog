<script lang="ts" setup>
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import Stage1 from './Stage1.vue';
import Stage2 from './Stage2.vue';
import Summary from './components/Summary.vue';
import {
    isStage1Part1Question,
    isStage1Part2Question,
    isStage2Question,
    type QuestionIdHolder
} from './composables/shared/questionIds';
import type { CompleteStage1Results, Config, Stage2Results, TestData } from './composables/shared/types';

const props = defineProps<{
    testData: TestData;
    appDebug?: boolean;
}>();

type CurrentView = 'start' | 'stage1' | 'stage2' | 'summary';

const currentView = ref<CurrentView>('start');
const isExtended = ref(false);
const stage1Results = ref<CompleteStage1Results | null>(null);
const stage2Results = ref<any>(null);

const stage1Part1Questions = computed(() => {
    return props.testData.questions.filter((q: QuestionIdHolder) => isStage1Part1Question(q));
});

const stage1Part2Questions = computed(() => {
    return props.testData.questions.filter((q: QuestionIdHolder) => isStage1Part2Question(q));
});

const stage1Questions = computed(() => {
    return [...stage1Part1Questions.value, ...stage1Part2Questions.value];
});

function calculateExtendedConfig(originalConfig: Config): Config {
    const config = JSON.parse(JSON.stringify(originalConfig)) as Config;
    // Reset skips for extended version
    config.stages.stage1.part1.maxSkips = 0;
    config.stages.stage1.part2.maxSkips = 0;
    config.stages.stage2.part1.maxSkips = 0;
    config.stages.stage2.part2.maxSkips = 0;
    config.stages.stage2.part3.maxSkips = 0;
    config.stages.stage2.part4.maxSkips = 0;

    // Stage 1
    const p1Pool = stage1Part1Questions.value.length;
    const p2Pool = stage1Part2Questions.value.length;

    if (p1Pool > 1) {
        config.stages.stage1.part1.maxQuestions = Math.max(originalConfig.stages.stage1.part1.maxQuestions, Math.floor((p1Pool - 1) * 0.6));
    }
    if (p2Pool > 1) {
        config.stages.stage1.part2.maxQuestions = Math.max(originalConfig.stages.stage1.part2.maxQuestions, Math.floor((p2Pool - 1) * 0.4));
    }

    if (config.stages.stage1.part1.thresholdX !== undefined) {
        config.stages.stage1.part1.thresholdX += 1;
    }
    if (config.stages.stage1.part2.thresholdX !== undefined) {
        config.stages.stage1.part2.thresholdX += 1;
    }
    if (config.stages.stage1.part2.thresholdY !== undefined) {
        config.stages.stage1.part2.thresholdY += 1;
    }

    // Stage 2 - Parts 1 & 2
    // We need to know the pool for each instinct, but Stage 2 parts share pools.
    // The instruction says: "w etapie 1 i etapie 2 maxQuestions będzie obliczany w taki sposób..."
    // In Stage 2, Parts 1 & 2 use one pool (dominant instinct), Parts 3 & 4 use another (secondary instinct).
    // Let's assume the 60/40 split applies to Part 1 and Part 2 of Stage 2 for the first pool,
    // and Part 3 and 4 remain as they are (as per "Część 3 i 4 w etapie 2 pozostawiamy tak jak jest").

    const stage2Pools = props.testData.questions.filter((q: QuestionIdHolder) => isStage2Question(q));
    // Each instinct has its own pool of questions.
    const getPoolSize = (prefix: string) => stage2Pools.filter((q) => String(q.id).startsWith(`${prefix}-`)).length;

    // Since we don't know the dominant instinct yet, we can't perfectly pre-calculate if pools differ.
    // However, usually they are symmetric. Let's apply the logic to part1 and part2 of stage2.
    // We might need to handle this dynamically in Stage2 or just use a representative pool size if they are same.
    const spPool = getPoolSize('sp');
    const soPool = getPoolSize('so');
    const sxPool = getPoolSize('sx');
    const minPool = Math.min(spPool, soPool, sxPool);

    if (minPool > 1) {
        config.stages.stage2.part1.maxQuestions = Math.max(originalConfig.stages.stage2.part1.maxQuestions, Math.floor((minPool - 1) * 0.6));
        config.stages.stage2.part2.maxQuestions = Math.max(originalConfig.stages.stage2.part2.maxQuestions, Math.floor((minPool - 1) * 0.4));
    }

    if (config.stages.stage2.part1.thresholdX !== undefined) {
        config.stages.stage2.part1.thresholdX += 1;
    }
    if (config.stages.stage2.part2.thresholdX !== undefined) {
        config.stages.stage2.part2.thresholdX += 1;
    }

    return config;
}

const activeConfig = computed(() => {
    if (isExtended.value) {
        return calculateExtendedConfig(props.testData.testConfig);
    }
    return props.testData.testConfig;
});

const stage1Config = computed(() => {
    return activeConfig.value.stages.stage1;
});

const stage2Questions = computed(() => {
    return props.testData.questions.filter((q: QuestionIdHolder) => isStage2Question(q));
});

const stage2Config = computed(() => {
    return activeConfig.value.stages.stage2;
});

const appDebug = computed(() => props.appDebug);

function startTest(extended: boolean) {
    isExtended.value = extended;
    currentView.value = 'stage1';
}

function handleStage1Complete(results: CompleteStage1Results) {
    stage1Results.value = results;
    if (results.isUnresolvable) {
        currentView.value = 'summary';
    } else {
        currentView.value = 'stage2';
    }
}

function handleStage2Complete(results: Stage2Results) {
    stage2Results.value = results;
    currentView.value = 'summary';
}
</script>

<template>
    <div class="p-4 md:p-6">
        <h1 class="mb-6 text-center text-3xl font-bold text-secondary-foreground">Test Enneagramu</h1>

        <div v-if="currentView === 'start'" class="mx-auto max-w-4xl rounded-lg bg-card p-8 shadow-md">
            <h2 class="mb-4 text-xl font-semibold text-foreground">Witaj w teście Enneagramu</h2>
            <p class="mb-4 text-primary">
                Ten test pomoże Ci odkryć Twój typ osobowości oraz kolejność instynktów. Składa się on z kilku etapów, w których będziesz wybierać
                stwierdzenia najlepiej opisujące Twoje zachowania i motywacje.
            </p>
            <div class="mb-6 space-y-2 text-primary">
                <p><strong>Zasady:</strong></p>
                <ul class="list-inside list-disc">
                    <li>Czytaj uważnie każde pytanie i dostępne odpowiedzi.</li>
                    <li>Wybieraj odpowiedzi, które najbardziej do Ciebie pasują (nie zawsze musisz wybrać maksymalną liczbę).</li>
                    <li>Jeśli naprawdę nie możesz się zdecydować, możesz skorzystać z ograniczonej puli pominięć.</li>
                    <li>Test najlepiej wypełniać w skupieniu, kierując się pierwszą intuicją.</li>
                </ul>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row">
                <Button class="flex-1 py-6 font-medium" size="lg" @click="startTest(false)"> Rozpocznij test (standardowy) </Button>
                <Button class="flex-1 py-6 font-medium" size="lg" variant="outline" @click="startTest(true)">
                    Wersja rozszerzona (dokładniejsza)
                </Button>
            </div>
            <p class="mt-4 text-center text-sm text-muted-foreground">
                Wersja rozszerzona zadaje więcej pytań dla uzyskania bardziej precyzyjnego wyniku.
            </p>
        </div>

        <Stage1
            v-if="currentView === 'stage1'"
            :config="stage1Config"
            :debug="appDebug"
            :questions="stage1Questions"
            @complete="handleStage1Complete"
        />

        <Stage2
            v-if="currentView === 'stage2' && stage1Results"
            :config="stage2Config"
            :debug="appDebug"
            :questions="stage2Questions"
            :results-stage1="stage1Results"
            @complete="handleStage2Complete"
        />

        <Summary v-if="currentView === 'summary'" :debug="appDebug" :stage1-results="stage1Results" :stage2-results="stage2Results" />
    </div>
</template>
