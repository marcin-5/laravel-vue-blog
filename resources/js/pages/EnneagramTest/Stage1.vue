<script lang="ts" setup>
import { computed } from 'vue';
import QuestionCard from './components/QuestionCard.vue';
import Stage1Debug from './components/Stage1Debug.vue';
import StageHeader from './components/StageHeader.vue';
import type { Question, Stage1Config } from './composables/shared/types';
import { useEnneagramStage1 } from './composables/useEnneagramStage1';

const props = defineProps<{
    questions: Question[];
    config: Stage1Config;
    debug?: boolean;
    autoConfirmSingleEnabled?: boolean;
}>();

const emit = defineEmits(['complete']);

const {
    currentPart,
    currentIndex,
    history,
    skips,
    selectedAnswers,
    answeredCountPart1,
    answeredCountPart2,
    part1Winner,
    scoresPart1,
    scoresPart2,
    currentConfig,
    maxAnswersPerQuestion,
    formattedDesc,
    partQuestions,
    currentQuestion,
    flatShuffledOptions,
    canSkip,
    toggleAnswer,
    confirmAnswers,
    handleSkip,
    goBack,
} = useEnneagramStage1(
    props.questions,
    props.config,
    emit,
    computed(() => props.autoConfirmSingleEnabled ?? true),
);
</script>

<template>
    <div class="mx-auto max-w-4xl p-4 md:p-6">
        <StageHeader
            :can-skip="canSkip"
            :description="formattedDesc"
            :max-skips="currentConfig.maxSkips"
            :part="currentPart"
            :skips="skips"
            :stage="1"
        />

        <QuestionCard
            v-if="currentQuestion"
            :can-skip="canSkip"
            :history-length="history.length"
            :max-answers="maxAnswersPerQuestion"
            :max-skips="currentConfig.maxSkips"
            :options="flatShuffledOptions"
            :question="currentQuestion"
            :selected-answers="selectedAnswers"
            :skips="skips"
            @back="goBack"
            @confirm="confirmAnswers"
            @skip="handleSkip"
            @toggle="toggleAnswer"
        />

        <Stage1Debug
            :answered-count1="answeredCountPart1"
            :answered-count2="answeredCountPart2"
            :current-index="currentIndex"
            :current-part="currentPart"
            :debug="debug"
            :history-length="history.length"
            :max-answers="maxAnswersPerQuestion"
            :max-questions1="config.part1.maxQuestions || 0"
            :max-questions2="config.part2.maxQuestions || 0"
            :max-skips="currentConfig.maxSkips"
            :part-questions-length="partQuestions.length"
            :part1-winner="part1Winner"
            :scores-part1="scoresPart1"
            :scores-part2="scoresPart2"
            :selected-count="selectedAnswers.length"
            :skips="skips"
        />
    </div>
</template>
