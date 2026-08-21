<script lang="ts" setup>
import { shallowRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { EnneagramOption, EnneagramQuestion, SelectedAnswer } from '../types';

const props = defineProps<{
    question: EnneagramQuestion;
    options: EnneagramOption[];
    selectedAnswers: SelectedAnswer[];
    answerLimit: number;
    skipCount: number;
    skipLimit: number;
    canSkip: boolean;
    canBack: boolean;
    autoConfirmSingle: boolean;
    processing: boolean;
}>();

const emit = defineEmits<{
    answer: [answers: SelectedAnswer[]];
    skip: [];
    back: [];
}>();

const { t } = useI18n();
const selectedAnswerState = shallowRef<SelectedAnswer[]>([]);

watch(
    () => props.selectedAnswers,
    (answers) => {
        selectedAnswerState.value = answers.map((answer) => ({ ...answer }));
    },
    { immediate: true, deep: true },
);

function isSelected(key: string): boolean {
    return selectedAnswerState.value.some((answer) => answer.key === key);
}

function toggle(option: EnneagramOption): void {
    if (props.processing) {
        return;
    }

    if (isSelected(option.key)) {
        selectedAnswerState.value = selectedAnswerState.value.filter((answer) => answer.key !== option.key);
        return;
    }

    if (selectedAnswerState.value.length < props.answerLimit) {
        selectedAnswerState.value = [...selectedAnswerState.value, option];

        if (props.autoConfirmSingle && props.answerLimit === 1) {
            emit('answer', answerPayload());
        }
    }
}

function answerPayload(): SelectedAnswer[] {
    return selectedAnswerState.value.map(({ key, value, category }) => ({ key, value, category }));
}

function submitAnswer(): void {
    if (selectedAnswerState.value.length > 0 && !props.processing) {
        emit('answer', answerPayload());
    }
}
</script>

<template>
    <Card class="mx-1 bg-card font-quicksand text-card-foreground">
        <CardHeader class="mx-2 px-2 md:px-3 lg:px-4">
            <CardTitle class="text-base text-pretty wrap-break-word text-foreground md:text-lg">
                {{ question.question }}
            </CardTitle>
            <div class="answer-progress flex items-center justify-between gap-4">
                <p aria-live="polite" class="text-sm text-muted-foreground">
                    {{ t('answered_questions') }}: {{ selectedAnswerState.length }}/{{ answerLimit }}
                </p>
                <p aria-live="polite" class="shrink-0 text-right text-sm text-muted-foreground">{{ t('skips') }}: {{ skipCount }}/{{ skipLimit }}</p>
            </div>
        </CardHeader>

        <CardContent class="px-2 md:px-3 lg:px-4">
            <div :aria-label="question.question" class="space-y-4 font-nunito md:space-y-3 lg:font-inter" role="group">
                <Button
                    v-for="option in options"
                    :key="option.key"
                    :aria-pressed="isSelected(option.key)"
                    :disabled="processing"
                    :variant="isSelected(option.key) ? 'secondary' : 'outline'"
                    class="h-auto min-h-10 w-full items-start justify-start rounded-lg border px-1 py-1 text-left text-sm leading-snug text-pretty wrap-break-word whitespace-normal md:px-2 md:py-2 md:text-base"
                    size="lg"
                    @click="toggle(option)"
                >
                    {{ option.value }}
                </Button>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div class="flex gap-2">
                    <Button :disabled="!canBack || processing" variant="outline" @click="emit('back')">
                        {{ t('back') }}
                    </Button>

                    <Button :disabled="!canSkip || processing" variant="muted" @click="emit('skip')">
                        {{ t('skip') }} ({{ skipCount }}/{{ skipLimit }})
                    </Button>
                </div>

                <Button :disabled="selectedAnswerState.length === 0 || processing" class="px-6" variant="secondary" @click="submitAnswer">
                    {{ processing ? t('loading') : t('next') }}
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
