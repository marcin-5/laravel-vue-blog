<script lang="ts" setup>
import type { PropType } from 'vue';
import QuestionCard from './components/QuestionCard.vue';
import Stage1Debug from './components/Stage1Debug.vue';
import Stage1Header from './components/Stage1Header.vue';
import type { Config, Question } from './composables/useEnneagramStage1';
import { useEnneagramStage1 } from './composables/useEnneagramStage1';

const props = defineProps({
    questions: {
        type: Array as PropType<Question[]>,
        required: true,
    },
    config: {
        type: Object as PropType<Config>,
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
    answeredCountPart1,
    answeredCountPart2,
    part1Winner,
    currentScores,
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
} = useEnneagramStage1(props.questions, props.config);
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
            :current-scores="currentScores"
            :debug="debug"
            :history-length="history.length"
            :max-answers="maxAnswersPerQuestion"
            :max-questions1="config.part1.maxQuestions || 0"
            :max-questions2="config.part2.maxQuestions || 0"
            :max-skips="currentConfig.maxSkips"
            :part-questions-length="partQuestions.length"
            :part1-winner="part1Winner"
            :selected-count="selectedAnswers.length"
            :skips="skips"
        />
    </div>
</template>
