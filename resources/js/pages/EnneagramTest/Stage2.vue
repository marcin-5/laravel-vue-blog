<script lang="ts" setup>
import type { PropType } from 'vue';
import { computed } from 'vue';
import QuestionCard from './components/QuestionCard.vue';
import Stage1Header from './components/Stage1Header.vue'; // We can reuse header style or create Stage2Header
import Stage2Debug from './components/Stage2Debug.vue';
import type { Config, Question } from './composables/useEnneagramStage2';
import { useEnneagramStage2 } from './composables/useEnneagramStage2';

const props = defineProps({
    questions: {
        type: Array as PropType<Question[]>,
        required: true,
    },
    config: {
        type: Object as PropType<Config>,
        required: true,
    },
    resultsStage1: {
        type: Object as PropType<{ dominant: string; secondary: string }>,
        required: true,
    },
    debug: {
        type: Boolean,
        default: false,
    },
});

const {
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
} = useEnneagramStage2(props.questions, props.config, props.resultsStage1);

const poolIndex = computed(() => {
    const instinct = currentInstinct.value;
    return instinctPoolIndices.value[instinct] ?? 0;
});

const formattedDesc = computed(() => {
    let desc = currentConfig.value.desc || '';
    desc = desc.replace(/%maxQuestions/g, String(currentConfig.value.maxQuestions));
    desc = desc.replace(/%maxSkips/g, String(currentConfig.value.maxSkips));
    desc = desc.replace(/%answersPerQuestion/g, String(maxAnswersPerQuestion.value));
    return desc;
});
</script>

<template>
    <div class="mx-auto max-w-2xl p-6">
        <Stage1Header :description="formattedDesc" :part="currentPart" />

        <QuestionCard
            v-if="currentQuestion"
            :can-skip="canSkip"
            :history-length="history.length"
            :max-answers="maxAnswersPerQuestion"
            :max-skips="currentConfig.maxSkips"
            :options="flatOptions"
            :question="currentQuestion"
            :selected-answers="selectedAnswers"
            :skips="skips"
            @back="goBack"
            @confirm="confirmAnswers"
            @skip="handleSkip"
            @toggle="toggleAnswer"
        />

        <div v-else class="rounded-lg bg-white p-12 text-center shadow">Brak pytań dla wybranej konfiguracji.</div>

        <div v-if="debug" class="my-4 text-center text-sm font-medium text-blue-600">
            Etap 2: Skupienie na instynkcie <span class="font-bold uppercase">{{ currentInstinct }}</span>
        </div>

        <Stage2Debug
            :current-index="currentIndex"
            :current-instinct="currentInstinct"
            :current-part="currentPart"
            :debug="debug"
            :history-length="history.length"
            :max-answers="maxAnswersPerQuestion"
            :max-skips="currentConfig.maxSkips"
            :selected-count="selectedAnswers.length"
            :skips="skips"
            :type-scores="typeScores"
            :pool-index="poolIndex"
        />
    </div>
</template>
