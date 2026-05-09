<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import QuestionCard from './components/QuestionCard.vue';
import Stage2Debug from './components/Stage2Debug.vue';
import StageHeader from './components/StageHeader.vue';
import { formatStageDescription } from './composables/shared/formatters';
import type { CompleteStage1Results, Question, Stage2Config } from './composables/shared/types';
import { useEnneagramStage2 } from './composables/useEnneagramStage2';

const props = defineProps<{
    questions: Question[];
    config: Stage2Config;
    resultsStage1: CompleteStage1Results;
    debug?: boolean;
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
} = useEnneagramStage2(props.questions, props.config, props.resultsStage1, emit);

const poolIndex = computed(() => {
    const instinct = currentInstinct.value;
    return instinctPoolIndices.value[instinct] ?? 0;
});

const formattedDesc = computed(() => {
    const descKey = `stage_descriptions.stage2.part${currentPart.value}`;
    return formatStageDescription(t(descKey), {
        maxQuestions: currentConfig.value.maxQuestions,
        maxSkips: currentConfig.value.maxSkips,
        answersPerQuestion: maxAnswersPerQuestion.value,
    });
});
</script>

<template>
    <div class="mx-auto max-w-4xl p-4 md:p-6">
        <StageHeader :can-skip="canSkip" :description="formattedDesc" :max-skips="currentConfig.maxSkips" :part="currentPart" :stage="2" />

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
