<script lang="ts" setup>
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
    toggle: [key: string | number, value: any, category?: string];
    confirm: [];
    skip: [];
    back: [];
}>();

const { t } = useI18n();

function isSelected(selectedAnswers: SelectedAnswer[], key: string | number) {
    return selectedAnswers.some((a) => String(a.key) === String(key));
}
</script>

<template>
    <Card v-if="question">
        <CardHeader>
            <CardTitle class="font-quicksand text-lg text-pretty break-words">
                {{ question.question }}
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-3">
                <Button
                    v-for="opt in options"
                    :key="opt.key"
                    :variant="isSelected(selectedAnswers, opt.key) ? 'secondary' : 'outline'"
                    class="w-full justify-start py-6 text-left font-recursive text-pretty break-words whitespace-normal"
                    size="lg"
                    @click="emit('toggle', opt.key, opt.value, opt.category)"
                >
                    {{ opt.value }}
                </Button>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div class="flex gap-2">
                    <Button :disabled="props.historyLength === 0" variant="outline" @click="emit('back')"> {{ t('back') }} </Button>
                    <Button :disabled="!props.canSkip || props.skips >= props.maxSkips" variant="muted" @click="emit('skip')">
                        {{ t('skip') }} ({{ props.skips }}/{{ props.maxSkips }})
                    </Button>
                </div>

                <Button
                    v-if="props.maxAnswers > 1"
                    :disabled="props.selectedAnswers.length < 1"
                    class="px-6"
                    variant="secondary"
                    @click="emit('confirm')"
                >
                    {{ t('next') }}
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
