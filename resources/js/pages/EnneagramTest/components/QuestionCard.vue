<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PropType } from 'vue';
import type { FlatOption, Question } from '../composables/useEnneagramStage1';

defineProps({
    question: {
        type: Object as PropType<Question>,
        required: true,
    },
    options: {
        type: Array as PropType<FlatOption[]>,
        required: true,
    },
    selectedAnswers: {
        type: Array as PropType<any[]>,
        required: true,
    },
    canSkip: {
        type: Boolean,
        required: true,
    },
    skips: {
        type: Number,
        required: true,
    },
    maxSkips: {
        type: Number,
        required: true,
    },
    maxAnswers: {
        type: Number,
        required: true,
    },
    historyLength: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits<{
    toggle: [key: string | number, value: any, category?: string | number];
    confirm: [];
    skip: [];
    back: [];
}>();

function isSelected(selectedAnswers: any[], key: string | number) {
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
                    <Button :disabled="historyLength === 0" variant="outline" @click="emit('back')"> Wstecz </Button>
                    <Button v-if="canSkip" :disabled="skips >= maxSkips" variant="muted" @click="emit('skip')">
                        Pomiń ({{ skips }}/{{ maxSkips }})
                    </Button>
                </div>

                <Button v-if="maxAnswers > 1" :disabled="selectedAnswers.length < 1" class="px-6" variant="secondary" @click="emit('confirm')">
                    Dalej
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
