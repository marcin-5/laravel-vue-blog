<script lang="ts" setup>
import type { PropType } from 'vue';

defineProps({
    debug: {
        type: Boolean,
        default: false,
    },
    currentPart: {
        type: Number,
        required: true,
    },
    currentIndex: {
        type: Number,
        required: true,
    },
    partQuestionsLength: {
        type: Number,
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
    selectedCount: {
        type: Number,
        required: true,
    },
    maxAnswers: {
        type: Number,
        required: true,
    },
    answeredCount1: {
        type: Number,
        required: true,
    },
    maxQuestions1: {
        type: Number,
        required: true,
    },
    answeredCount2: {
        type: Number,
        required: true,
    },
    maxQuestions2: {
        type: Number,
        required: true,
    },
    scoresPart1: {
        type: Object as PropType<Record<string, number>>,
        required: true,
    },
    scoresPart2: {
        type: Object as PropType<Record<string, number>>,
        required: true,
    },
    part1Winner: {
        type: String as PropType<string | null>,
        default: null,
    },
    historyLength: {
        type: Number,
        required: true,
    },
});
</script>

<template>
    <div v-if="debug" class="mt-6 flex flex-col gap-4">
        <!-- Part 2 Debug Frame -->
        <div
            v-if="currentPart >= 2"
            :class="[
                'rounded-md border p-4 text-sm transition-colors',
                currentPart === 2 ? 'border-secondary-foreground bg-secondary' : 'border-border bg-muted/40 text-muted-foreground',
            ]"
        >
            <div :class="currentPart === 2 ? 'text-primary' : 'text-foreground'" class="mb-2 font-semibold">DEBUG: Statystyki (Etap 1 - Część 2)</div>
            <div class="grid grid-cols-2 gap-2">
                <div v-if="currentPart === 2">
                    Pytanie: <span class="text-foreground">{{ currentIndex + 1 }}/{{ partQuestionsLength }}</span>
                </div>
                <div v-if="currentPart === 2">
                    Pominięcia: <span class="text-foreground">{{ skips }}/{{ maxSkips }}</span>
                </div>
                <div v-if="currentPart === 2">
                    Wybrane: <span class="text-foreground">{{ selectedCount }}/{{ maxAnswers }}</span>
                </div>
                <div>
                    Odpowiedziane: <span class="text-foreground">{{ answeredCount2 }}/{{ maxQuestions2 }}</span>
                </div>
            </div>
            <div class="mt-2">
                Wyniki części 2:
                <span class="text-foreground"> SP={{ scoresPart2.sp }}, SO={{ scoresPart2.so }}, SX={{ scoresPart2.sx }} </span>
            </div>
            <div v-if="currentPart === 2" class="mt-2">
                Historia kroków: <span class="text-foreground">{{ historyLength }}</span>
            </div>
        </div>

        <!-- Part 1 Debug Frame -->
        <div
            :class="[
                'rounded-md border p-4 text-sm transition-colors',
                currentPart === 1 ? 'border-secondary-foreground bg-secondary' : 'border-border bg-muted/40 text-muted-foreground',
            ]"
        >
            <div :class="currentPart === 1 ? 'text-primary' : 'text-foreground'" class="mb-2 font-semibold">DEBUG: Statystyki (Etap 1 - Część 1)</div>
            <div class="grid grid-cols-2 gap-2">
                <div v-if="currentPart === 1">
                    Pytanie: <span class="text-foreground">{{ currentIndex + 1 }}/{{ partQuestionsLength }}</span>
                </div>
                <div v-if="currentPart === 1">
                    Pominięcia: <span class="text-foreground">{{ skips }}/{{ maxSkips }}</span>
                </div>
                <div v-if="currentPart === 1">
                    Wybrane: <span class="text-foreground">{{ selectedCount }}/{{ maxAnswers }}</span>
                </div>
                <div>
                    Odpowiedziane: <span class="text-foreground">{{ answeredCount1 }}/{{ maxQuestions1 }}</span>
                </div>
            </div>
            <div class="mt-2">
                Wyniki części 1:
                <span class="text-foreground"> SP={{ scoresPart1.sp }}, SO={{ scoresPart1.so }}, SX={{ scoresPart1.sx }} </span>
            </div>
            <div class="mt-2">
                Zwycięzca części 1 (lider):
                <span class="font-bold text-foreground">{{ part1Winner || (currentPart > 1 ? 'Brak' : '-') }}</span>
            </div>
            <div v-if="currentPart === 1" class="mt-2">
                Historia kroków: <span class="text-foreground">{{ historyLength }}</span>
            </div>
        </div>
    </div>
</template>
