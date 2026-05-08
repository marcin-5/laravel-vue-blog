<script lang="ts" setup>
import type { InstinctScores } from '../composables/shared/types';
import DebugInstinctScores from './debug/DebugInstinctScores.vue';
import DebugPanel from './debug/DebugPanel.vue';

defineProps<{
    debug?: boolean;
    currentPart: number;
    currentIndex: number;
    partQuestionsLength: number;
    skips: number;
    maxSkips: number;
    selectedCount: number;
    maxAnswers: number;
    answeredCount1: number;
    maxQuestions1: number;
    answeredCount2: number;
    maxQuestions2: number;
    scoresPart1: InstinctScores;
    scoresPart2: InstinctScores;
    part1Winner?: string | null;
    historyLength: number;
}>();
</script>

<template>
    <div v-if="debug" class="mt-6 flex flex-col gap-4">
        <!-- Part 2 Debug Frame -->
        <DebugPanel :active="currentPart === 2" :visible="currentPart >= 2" title="DEBUG: Statystyki (Etap 1 - Część 2)">
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
            <DebugInstinctScores :scores="scoresPart2" />
            <div v-if="currentPart === 2" class="mt-2">
                Historia kroków: <span class="text-foreground">{{ historyLength }}</span>
            </div>
        </DebugPanel>

        <!-- Part 1 Debug Frame -->
        <DebugPanel :active="currentPart === 1" :visible="true" title="DEBUG: Statystyki (Etap 1 - Część 1)">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    Pytanie:
                    <span class="text-foreground"
                        >{{ currentPart === 1 ? currentIndex + 1 : maxQuestions1 }}/{{
                            currentPart === 1 ? partQuestionsLength : maxQuestions1
                        }}</span
                    >
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
            <DebugInstinctScores :scores="scoresPart1" />
            <div class="mt-2">
                Zwycięzca części 1 (lider):
                <span class="font-bold text-foreground">{{ part1Winner || (currentPart > 1 ? 'Brak' : '-') }}</span>
            </div>
            <div v-if="currentPart === 1" class="mt-2">
                Historia kroków: <span class="text-foreground">{{ historyLength }}</span>
            </div>
        </DebugPanel>
    </div>
</template>
