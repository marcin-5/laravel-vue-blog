<script lang="ts" setup>
import QuestionCard from './components/QuestionCard.vue';
import type { EnneagramTestState, SelectedAnswer } from './types';

const props = defineProps<{
    state: EnneagramTestState;
    autoConfirmSingle: boolean;
    processing: boolean;
}>();

const emit = defineEmits<{
    answer: [answers: SelectedAnswer[]];
    skip: [];
    back: [];
}>();
</script>

<template>
    <div class="mx-auto max-w-4xl p-2 md:p-3 lg:p-6">
        <QuestionCard
            v-if="props.state.question"
            :answer-limit="props.state.answer_limit"
            :can-back="props.state.allowed_actions.back"
            :can-skip="props.state.allowed_actions.skip"
            :auto-confirm-single="props.autoConfirmSingle"
            :options="props.state.options"
            :processing="props.processing"
            :question="props.state.question"
            :selected-answers="props.state.selected_answers"
            :skip-count="props.state.skip_count"
            :skip-limit="props.state.skip_limit"
            @answer="emit('answer', $event)"
            @back="emit('back')"
            @skip="emit('skip')"
        />
    </div>
</template>
