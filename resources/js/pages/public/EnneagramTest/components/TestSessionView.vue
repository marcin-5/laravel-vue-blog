<script lang="ts" setup>
import { shallowRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Stage1 from '../Stage1.vue';
import Stage2 from '../Stage2.vue';
import ProgressIndicators from './ProgressIndicators.vue';
import TestMap from './TestMap.vue';
import type { EnneagramTestState, SelectedAnswer, TestAction } from '../types';

const props = defineProps<{
    state: EnneagramTestState;
    autoConfirmSingle: boolean;
    processing: boolean;
}>();

const emit = defineEmits<{
    action: [action: TestAction, answers?: SelectedAnswer[]];
}>();

const { t } = useI18n();
const transitionMessage = shallowRef('');

watch(
    () => [props.state.stage, props.state.part] as const,
    ([stage, part], previous) => {
        if (!previous || (stage === previous[0] && part === previous[1])) {
            return;
        }

        transitionMessage.value = t('stage_started', { stage, part });
    },
);
</script>

<template>
    <section class="mx-auto max-w-4xl" aria-labelledby="enneagram-session-title">
        <h2 id="enneagram-session-title" class="sr-only">{{ $t('question_progress') }}</h2>
        <p class="sr-only" aria-live="polite">{{ transitionMessage }}</p>

        <TestMap :test-map="props.state.test_map" />

        <Stage1
            v-if="props.state.stage === 1"
            :processing="props.processing"
            :auto-confirm-single="props.autoConfirmSingle"
            :state="props.state"
            @answer="emit('action', 'answer', $event)"
            @back="emit('action', 'back')"
            @skip="emit('action', 'skip')"
        />
        <Stage2
            v-else
            :processing="props.processing"
            :auto-confirm-single="props.autoConfirmSingle"
            :state="props.state"
            @answer="emit('action', 'answer', $event)"
            @back="emit('action', 'back')"
            @skip="emit('action', 'skip')"
        />

        <ProgressIndicators :state="props.state" />
    </section>
</template>
