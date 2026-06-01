<script lang="ts" setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import Stage1 from './Stage1.vue';
import Stage2 from './Stage2.vue';
import Summary from './components/Summary.vue';
import en from './locales/en.json';
import pl from './locales/pl.json';
import '@/../css/enneagram-test.css';
import { isStage1Part1Question, isStage1Part2Question, isStage2Question, type QuestionIdHolder } from './composables/shared/questionIds';
import type { CompleteStage1Results, Config, Stage2Results, TestData } from './composables/shared/types';

const props = defineProps<{
    testData: TestData;
    initialLocale: string;
    appDebug?: boolean;
    autoConfirmSingleDefault?: boolean;
}>();

const { t, tm, locale, mergeLocaleMessage } = useI18n();

// Load component-specific translations
mergeLocaleMessage('en', en);
mergeLocaleMessage('pl', pl);

// Determine initial locale from server-side prop
locale.value = props.initialLocale || 'en';

function toggleLanguage() {
    locale.value = locale.value === 'pl' ? 'en' : 'pl';
}

type CurrentView = 'start' | 'stage1' | 'stage2' | 'summary';

const currentView = ref<CurrentView>('start');
const selectedTheme = ref('theme-light-standard');

// Load theme from localStorage on mount
onMounted(() => {
    const savedTheme = localStorage.getItem('enneagram-test-theme');
    if (savedTheme && themes.includes(savedTheme)) {
        selectedTheme.value = savedTheme;
    } else {
        // Default based on system preference
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        selectedTheme.value = prefersDark ? 'theme-dark-standard' : 'theme-light-standard';
    }
});

// Persist theme to localStorage
watch(selectedTheme, (newTheme) => {
    localStorage.setItem('enneagram-test-theme', newTheme);
});

const isExtended = ref(false);
const autoConfirmSingle = ref<boolean>(props.autoConfirmSingleDefault ?? true);
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

    if (config.stages.stage1.part1.minLead !== undefined) {
        config.stages.stage1.part1.minLead += 1;
    }
    if (config.stages.stage1.part2.minLead !== undefined) {
        config.stages.stage1.part2.minLead += 1;
    }
    if (config.stages.stage1.part2.minLeadAlternative !== undefined) {
        config.stages.stage1.part2.minLeadAlternative += 1;
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

    if (config.stages.stage2.part1.minLead !== undefined) {
        config.stages.stage2.part1.minLead += 1;
    }
    if (config.stages.stage2.part2.minLead !== undefined) {
        config.stages.stage2.part2.minLead += 1;
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

const themes = [
    'theme-light-standard',
    'theme-light-blue',
    'theme-light-warm',
    'theme-light-pastel',
    'theme-dark-standard',
    'theme-dark-forest',
    'theme-dark-purple',
    'theme-dark-amber',
];

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
    <div :class="['enneagram-test-container p-2 md:p-3 lg:p-6', selectedTheme]">
        <div class="mx-auto mb-6 flex max-w-4xl items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-foreground">{{ t('theme_selection') }}</span>
                <select v-model="selectedTheme" class="rounded border border-border bg-card p-1 text-sm text-foreground outline-none">
                    <option v-for="theme in themes" :key="theme" :value="theme">
                        {{ t(`themes.${theme.replace('theme-', '')}`) }}
                    </option>
                </select>
            </div>
            <Button size="sm" variant="ghost" @click="toggleLanguage">
                {{ locale === 'pl' ? 'EN' : 'PL' }}
            </Button>
        </div>

        <h1 class="mb-6 text-center font-recursive text-3xl font-bold">{{ t('title') }}</h1>

        <div v-if="currentView === 'start'" class="mx-auto max-w-4xl rounded-lg bg-card p-8 shadow-md">
            <h2 class="mb-4 font-quicksand text-xl font-semibold text-foreground">{{ t('welcome') }}</h2>
            <p class="mb-4 text-primary">
                {{ t('description') }}
            </p>
            <div class="mb-6 space-y-2 text-primary">
                <p>
                    <strong>{{ t('rules_title') }}</strong>
                </p>
                <ul class="list-inside list-disc">
                    <li v-for="(rule, index) in tm('rules')" :key="index">
                        {{ rule }}
                    </li>
                </ul>
            </div>

            <div class="mb-6 rounded-md border p-4">
                <label class="flex items-start gap-3">
                    <input v-model="autoConfirmSingle" class="mt-1 size-4" type="checkbox" />
                    <span class="flex flex-col">
                        <span class="font-medium">{{ t('auto_confirm_single_label') }}</span>
                        <span class="text-sm text-muted-foreground">{{ t('auto_confirm_single_help') }}</span>
                    </span>
                </label>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row">
                <Button class="flex-1 py-6 font-medium" size="lg" @click="startTest(false)"> {{ t('start_standard') }} </Button>
                <Button class="flex-1 py-6 font-medium" size="lg" variant="outline" @click="startTest(true)">
                    {{ t('start_extended') }}
                </Button>
            </div>
            <p class="mt-4 text-center text-sm text-muted-foreground">
                {{ t('extended_info') }}
            </p>
        </div>

        <Stage1
            v-if="currentView === 'stage1'"
            :auto-confirm-single-enabled="autoConfirmSingle"
            :config="stage1Config"
            :debug="appDebug"
            :questions="stage1Questions"
            @complete="handleStage1Complete"
        />

        <Stage2
            v-if="currentView === 'stage2' && stage1Results"
            :auto-confirm-single-enabled="autoConfirmSingle"
            :config="stage2Config"
            :debug="appDebug"
            :questions="stage2Questions"
            :results-stage1="stage1Results"
            @complete="handleStage2Complete"
        />

        <Summary v-if="currentView === 'summary'" :debug="appDebug" :stage1-results="stage1Results" :stage2-results="stage2Results" />
    </div>
</template>
