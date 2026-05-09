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
    <Card v-if="question" class="mx-1 font-quicksand">
        <CardHeader class="mx-2 px-2 md:px-3 lg:px-4">
            <CardTitle class="text-base text-pretty break-words md:text-lg">
                {{ question.question }}
            </CardTitle>
        </CardHeader>
        <CardContent class="px-2 md:px-3 lg:px-4">
            <div class="space-y-4 font-nunito md:space-y-3 lg:font-inter">
                <Button
                    v-for="opt in options"
                    :key="opt.key"
                    :variant="isSelected(selectedAnswers, opt.key) ? 'secondary' : 'outline'"
                    class="w-full justify-start rounded-lg px-1 py-4 text-left text-sm leading-snug text-pretty break-words whitespace-normal md:px-4 md:py-5 md:text-base"
                    size="lg"
                    @click="emit('toggle', opt.key, opt.value, opt.category)"
                >
                    {{ opt.value }}
                </Button>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div class="flex gap-2">
                    <Button :disabled="props.historyLength === 0" variant="outline" @click="emit('back')"> {{ t('back') }} </Button>
                    <Button :disabled="props.selectedAnswers.length > 0 || props.skips >= props.maxSkips" variant="muted" @click="emit('skip')">
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
