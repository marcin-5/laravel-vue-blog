<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import QuestionCard from './components/QuestionCard.vue';
import Stage2Debug from './components/Stage2Debug.vue';
import StageHeader from './components/StageHeader.vue';
import type { CompleteStage1Results, Question, Stage2Config } from './composables/shared/types';
import { useEnneagramStage2 } from './composables/useEnneagramStage2';

const props = defineProps<{
    questions: Question[];
    config: Stage2Config;
    resultsStage1: CompleteStage1Results;
    debug?: boolean;
    autoConfirmSingleEnabled?: boolean;
}>();

const emit = defineEmits(['complete']);

const { t } = useI18n();

const {
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
    partQuestions,
} = useEnneagramStage2(
    props.questions,
    props.config,
    props.resultsStage1,
    emit,
    computed(() => props.autoConfirmSingleEnabled ?? true),
);

const minQuestions = computed(() => {
    // Stage 2 usually has a lead requirement of 2 points.
    // Part 1 & 3: answersPerQuestion usually 2 (min = 1)
    // Part 2 & 4: answersPerQuestion usually 1 (min = 2)
    const threshold = 2;
    const answersPerQuestion = currentConfig.value.answersPerQuestion || 1;
    return Math.ceil(threshold / answersPerQuestion);
});

const poolIndex = computed(() => {
    const instinct = currentInstinct.value;
    return instinctPoolIndices.value[instinct] ?? 0;
});

const formattedDesc = computed(() => t('max_answers', { maxAnswers: maxAnswersPerQuestion.value }));
</script>

<template>
    <div class="mx-auto max-w-4xl p-2 md:p-3 lg:p-6">
        <StageHeader
            :can-skip="canSkip"
            :description="formattedDesc"
            :max-skips="currentConfig.maxSkips"
            :part="currentPart"
            :skips="skips"
            :stage="2"
            :current-question-number="currentIndex"
            :max-questions-standard="currentConfig.maxQuestions"
            :total-pool-size="partQuestions.length"
            :min-questions-standard="minQuestions"
        />

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

        <div v-else class="rounded-lg bg-white p-12 text-center shadow">{{ t('no_questions') }}</div>

        <div v-if="debug" class="my-4 text-center text-sm font-medium text-secondary-foreground">
            {{ t('focus_on_instinct', { instinct: currentInstinct.toUpperCase() }) }}
        </div>

        <Stage2Debug
            :current-index="currentIndex"
            :current-instinct="currentInstinct"
            :current-part="currentPart"
            :debug="debug"
            :history-length="history.length"
            :max-answers="maxAnswersPerQuestion"
            :max-skips="currentConfig.maxSkips"
            :pool-index="poolIndex"
            :results-stage1="resultsStage1"
            :scores-per-part="scoresPerPart"
            :selected-count="selectedAnswers.length"
            :skips="skips"
            :type-scores="typeScores"
        />
    </div>
</template>
