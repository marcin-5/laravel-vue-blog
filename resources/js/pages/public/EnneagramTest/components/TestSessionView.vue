<script lang="ts" setup>
import Stage1 from '../Stage1.vue';
import Stage2 from '../Stage2.vue';
import type { EnneagramTestState, SelectedAnswer, TestAction } from '../types';

const props = defineProps<{
    state: EnneagramTestState;
    autoConfirmSingle: boolean;
    processing: boolean;
}>();

const emit = defineEmits<{
    action: [action: TestAction, answers?: SelectedAnswer[]];
}>();
</script>

<template>
    <section class="mx-auto max-w-4xl" aria-labelledby="enneagram-session-title">
        <div class="mb-6" aria-live="polite">
            <div class="mb-2 flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">
                        {{ $t('stage') }} {{ props.state.stage }} · {{ $t('part') }} {{ props.state.part }}
                    </p>
                    <h2 id="enneagram-session-title" class="sr-only">{{ $t('question_progress') }}</h2>
                </div>
                <span class="text-sm font-semibold text-foreground">
                    {{ props.state.progress.current }}/{{ props.state.progress.total }}
                </span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-secondary/30" role="progressbar" :aria-valuemax="props.state.progress.total" :aria-valuenow="props.state.progress.current">
                <div
                    class="h-full rounded-full bg-primary transition-[width] duration-300 motion-reduce:transition-none"
                    :style="{ width: `${props.state.progress.total ? (props.state.progress.current / props.state.progress.total) * 100 : 0}%` }"
                />
            </div>
        </div>

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
    </section>
</template>