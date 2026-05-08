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
    scoresPerPart: { type: Object as PropType<Record<number, Record<string, number>>>, required: true },
    currentInstinct: { type: String, required: true },
    poolIndex: { type: Number, required: true },
    resultsStage1: { type: Object as PropType<any>, required: false },
});
</script>

<template>
    <div v-if="debug" class="mt-6 flex flex-col gap-4">
        <div
            v-for="part in [4, 3, 2, 1]"
            v-show="part <= currentPart"
            :key="part"
            :class="[
                'rounded-md border p-4 text-sm transition-colors',
                part === currentPart ? 'border-secondary-foreground bg-secondary' : 'border-border bg-muted/40 text-muted-foreground',
            ]"
        >
            <div :class="part === currentPart ? 'text-primary' : 'text-foreground'" class="mb-2 font-semibold">
                DEBUG: Statystyki (Etap 2 - Część {{ part }})
            </div>
            <div v-if="part === currentPart" class="grid grid-cols-2 gap-2">
                <div>
                    Pytanie: <span class="text-foreground"> {{ currentIndex + 1 }} </span>
                    <span class="text-[10px]">(Pool: {{ poolIndex + 1 }})</span>
                </div>
                <div>
                    Instynkt: <span class="font-bold text-foreground uppercase">{{ currentInstinct }}</span>
                </div>
                <div>
                    Pominięcia: <span class="text-foreground">{{ skips }}/{{ maxSkips }}</span>
                </div>
                <div>
                    Wybrane: <span class="text-foreground"> {{ selectedCount }}/{{ maxAnswers }} </span>
                </div>
                <div>
                    Historia: <span class="text-foreground">{{ historyLength }}</span>
                </div>
            </div>

            <div class="mt-4 border-t border-border pt-4">
                <div class="mb-2 font-semibold text-foreground underline">Wyniki typów w tej części:</div>
                <div class="grid grid-cols-3 gap-2">
                    <div v-for="(score, type) in scoresPerPart[part]" :key="type" class="flex justify-between border-b border-border pr-2 pb-1">
                        <span class="text-muted-foreground">Typ {{ type }}:</span>
                        <span class="font-bold text-foreground">{{ score }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Summary -->
        <div class="mt-4 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
            <div class="mb-2 font-semibold text-foreground underline">Wyniki sumaryczne (Etap 2):</div>
            <div class="grid grid-cols-3 gap-2">
                <div v-for="(score, type) in typeScores" :key="type" class="flex justify-between border-b border-border pr-2 pb-1">
                    <span class="text-muted-foreground">Typ {{ type }}:</span>
                    <span class="font-bold text-foreground">{{ score }}</span>
                </div>
            </div>
            <div class="mt-4 text-[10px] opacity-70">Logika Etapu 2: Części 1&2 używają Dominującego, 3&4 Środkowego. Części 2&4 filtrują opcje.</div>
        </div>

        <!-- Stage 1 Static Frames -->
        <template v-if="resultsStage1">
            <div class="mt-4 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
                <div class="mb-2 font-semibold text-foreground">DEBUG: Statystyki (Etap 1 - Część 2)</div>
                <div class="mt-2">
                    Wyniki części 2:
                    <span class="text-foreground">
                        SP={{ resultsStage1.scoresPart2.sp }}, SO={{ resultsStage1.scoresPart2.so }}, SX={{ resultsStage1.scoresPart2.sx }}
                    </span>
                </div>
            </div>
            <div class="mt-4 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
                <div class="mb-2 font-semibold text-foreground">DEBUG: Statystyki (Etap 1 - Część 1)</div>
                <div class="mt-2">
                    Wyniki części 1:
                    <span class="text-foreground">
                        SP={{ resultsStage1.scoresPart1.sp }}, SO={{ resultsStage1.scoresPart1.so }}, SX={{ resultsStage1.scoresPart1.sx }}
                    </span>
                </div>
                <div class="mt-2">
                    Zwycięzca części 1 (lider):
                    <span class="font-bold text-foreground">{{ resultsStage1.part1Winner || '-' }}</span>
                </div>
            </div>
        </template>
    </div>
</template>
