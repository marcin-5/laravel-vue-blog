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
    currentScores: {
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
    <div v-if="debug" class="mt-6 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
        <div class="mb-2 font-semibold text-foreground">DEBUG: Statystyki (Etap 1)</div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                Part: <span class="text-foreground">{{ currentPart }}</span>
            </div>
            <div>
                Pytanie:
                <span class="text-foreground"> {{ currentIndex + 1 }}/{{ partQuestionsLength }} </span>
            </div>
            <div>
                Pominięcia:
                <span class="text-foreground">{{ skips }}/{{ maxSkips }}</span>
            </div>
            <div>
                Wybrane:
                <span class="text-foreground"> {{ selectedCount }}/{{ maxAnswers }} </span>
            </div>
            <div>
                Odpowiedziane (P1/P2):
                <span class="text-foreground"> {{ answeredCount1 }}/{{ maxQuestions1 }} / {{ answeredCount2 }}/{{ maxQuestions2 }} </span>
            </div>
        </div>
        <div class="mt-2">
            Wyniki bieżącej części:
            <span class="text-foreground"> SP={{ currentScores.sp }}, SC={{ currentScores.so }}, SX={{ currentScores.sx }} </span>
        </div>
        <div class="mt-2">
            Wynik części 1 (lider):
            <span class="text-foreground">{{ part1Winner || '-' }}</span>
        </div>
        <div class="mt-2">
            Historia kroków:
            <span class="text-foreground">{{ historyLength }}</span>
        </div>
    </div>
</template>
