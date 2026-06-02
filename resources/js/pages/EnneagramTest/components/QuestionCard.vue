<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { FlatOption, Question, SelectedAnswer } from '../composables/shared/types';

const props = defineProps<{
    question: Question;
    options: FlatOption[];
    selectedAnswers: SelectedAnswer[];
    canSkip: boolean;
    skips: number;
    maxSkips: number;
    maxAnswers: number;
    historyLength: number;
}>();

const emit = defineEmits<{
    toggle: [key: string | number, value: string, category?: string];
    confirm: [];
    skip: [];
    back: [];
}>();

const { t } = useI18n();

const selectedAnswerKeys = computed(() => new Set(props.selectedAnswers.map((answer) => String(answer.key))));

const canGoBack = computed(() => props.historyLength > 0);
const canConfirm = computed(() => props.selectedAnswers.length > 0);
const canSkipQuestion = computed(() => props.canSkip && props.selectedAnswers.length === 0 && props.skips < props.maxSkips);

function isSelected(key: string | number) {
    return selectedAnswerKeys.value.has(String(key));
}
</script>

<template>
    <Card v-if="question" class="mx-1 bg-card font-quicksand text-card-foreground">
        <CardHeader class="mx-2 px-2 md:px-3 lg:px-4">
            <CardTitle class="text-base text-pretty wrap-break-word text-foreground md:text-lg">
                {{ question.question }}
            </CardTitle>
        </CardHeader>

        <CardContent class="px-2 md:px-3 lg:px-4">
            <div class="space-y-4 font-nunito md:space-y-3 lg:font-inter">
                <Button
                    v-for="opt in options"
                    :key="opt.key"
                    :variant="isSelected(opt.key) ? 'secondary' : 'outline'"
                    class="w-full justify-start rounded-lg border px-1 py-4 text-left text-sm leading-snug text-pretty wrap-break-word whitespace-normal md:px-4 md:py-6 md:text-base"
                    size="lg"
                    @click="emit('toggle', opt.key, opt.value, opt.category)"
                >
                    {{ opt.value }}
                </Button>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div class="flex gap-2">
                    <Button :disabled="!canGoBack" variant="outline" @click="emit('back')">
                        {{ t('back') }}
                    </Button>

                    <Button :disabled="!canSkipQuestion" variant="muted" @click="emit('skip')"> {{ t('skip') }} ({{ skips }}/{{ maxSkips }}) </Button>
                </div>

                <Button :disabled="!canConfirm" class="px-6" variant="secondary" @click="emit('confirm')">
                    {{ t('next') }}
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
