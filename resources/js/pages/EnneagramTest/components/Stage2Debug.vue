<script lang="ts" setup>
import type { CompleteStage1Results } from '../composables/shared/types';
import DebugPanel from './debug/DebugPanel.vue';
import DebugScoreGrid from './debug/DebugScoreGrid.vue';

defineProps<{
    debug?: boolean;
    currentPart: number;
    currentIndex: number;
    skips: number;
    maxSkips: number;
    maxAnswers: number;
    selectedCount: number;
    historyLength: number;
    typeScores: Record<string, number>;
    scoresPerPart: Record<number, Record<string, number>>;
    currentInstinct: string;
    poolIndex: number;
    resultsStage1?: CompleteStage1Results | null;
}>();
</script>

<template>
    <div v-if="debug" class="mt-6 flex flex-col gap-4">
        <DebugPanel
            v-for="part in [4, 3, 2, 1]"
            :key="part"
            :active="part === currentPart"
            :title="`DEBUG: Statystyki (Etap 2 - Część ${part})`"
            :visible="part <= currentPart"
        >
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

            <DebugScoreGrid :scores="scoresPerPart[part]" label-prefix="Typ " title="Wyniki typów w tej części:" />
        </DebugPanel>

        <!-- Global Summary -->
        <div class="mt-4 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
            <DebugScoreGrid :scores="typeScores" label-prefix="Typ " title="Wyniki sumaryczne (Etap 2):" />
            <div class="mt-4 text-[10px] opacity-70">Logika Etapu 2: Części 1&2 używają Dominującego, 3&4 Środkowego. Części 2&4 filtrują opcje.</div>
        </div>

        <!-- Stage 1 Static Frames -->
        <template v-if="resultsStage1">
            <div class="mt-4 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
                <div class="mb-2 font-semibold text-foreground">DEBUG: Statystyki (Etap 1 - Część 2)</div>
                <div class="mt-2">
                    Wyniki części 2:
                    <span class="text-foreground uppercase">
                        SP={{ resultsStage1.scoresPart2.sp }}, SO={{ resultsStage1.scoresPart2.so }}, SX={{ resultsStage1.scoresPart2.sx }}
                    </span>
                </div>
            </div>
            <div class="mt-4 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
                <div class="mb-2 font-semibold text-foreground">DEBUG: Statystyki (Etap 1 - Część 1)</div>
                <div class="mt-2">
                    Wyniki części 1:
                    <span class="text-foreground uppercase">
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
