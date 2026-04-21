<script lang="ts" setup>
import type { PropType } from 'vue';

defineProps({
    debug: { type: Boolean, default: false },
    currentPart: { type: Number, required: true },
    currentIndex: { type: Number, required: true },
    skips: { type: Number, required: true },
    maxSkips: { type: Number, required: true },
    maxAnswers: { type: Number, required: true },
    selectedCount: { type: Number, required: true },
    historyLength: { type: Number, required: true },
    typeScores: { type: Object as PropType<Record<string, number>>, required: true },
    currentInstinct: { type: String, required: true },
    poolIndex: { type: Number, required: true },
});
</script>

<template>
    <div v-if="debug" class="mt-6 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
        <div class="mb-2 font-semibold text-foreground">DEBUG: Statystyki (Etap 2)</div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                Part: <span class="text-foreground">{{ currentPart }} / 4</span>
            </div>
            <div>
                Pytanie:
                <span class="text-foreground"> {{ currentIndex + 1 }} </span>
                <span class="text-[10px]">(Pool: {{ poolIndex + 1 }})</span>
            </div>
            <div>
                Instynkt:
                <span class="font-bold text-foreground uppercase">{{ currentInstinct }}</span>
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
                Historia:
                <span class="text-foreground">{{ historyLength }}</span>
            </div>
        </div>

        <div class="mt-4 border-t border-border pt-4">
            <div class="mb-2 font-semibold text-foreground underline">Wyniki typów (Suma Etap 2):</div>
            <div class="grid grid-cols-3 gap-2">
                <div v-for="(score, type) in typeScores" :key="type" class="flex justify-between border-b border-border pr-2 pb-1">
                    <span class="text-muted-foreground">Typ {{ type }}:</span>
                    <span class="font-bold text-foreground">{{ score }}</span>
                </div>
            </div>
        </div>

        <div class="mt-4 text-[10px] opacity-70">Logika Etapu 2: Części 1&2 używają Dominującego, 3&4 Środkowego. Części 2&4 filtrują opcje.</div>
    </div>
</template>
