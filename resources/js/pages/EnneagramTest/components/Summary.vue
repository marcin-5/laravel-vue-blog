<script lang="ts" setup>
import { computed } from 'vue';

const props = defineProps<{
    stage1Results: any;
    stage2Results: any;
    debug?: boolean;
}>();

const sortedTypes = computed(() => {
    if (!props.stage2Results) return [];
    return Object.entries(props.stage2Results)
        .map(([type, score]) => ({ type, score: score as number }))
        .sort((a, b) => b.score - a.score);
});

const topType = computed(() => sortedTypes.value[0]);

const maxScore = computed(() => {
    if (sortedTypes.value.length === 0) return 0;
    return sortedTypes.value[0].score;
});

function restart() {
    window.location.reload();
}
</script>

<template>
    <div class="mx-auto max-w-2xl bg-card p-6">
        <div class="rounded-lg p-8 text-center shadow-lg">
            <h2 class="mb-6 text-3xl font-bold text-foreground">
                {{ stage1Results?.isUnresolvable ? 'Nie udało się dokończyć testu' : 'Podsumowanie Testu' }}
            </h2>

            <div v-if="stage1Results?.isUnresolvable" class="mb-8 text-center">
                <p class="text-lg text-muted-foreground">
                    Niestety, na podstawie Twoich odpowiedzi nie udało się jednoznacznie określić kolejności instynktów.
                </p>
            </div>

            <div v-else class="mb-8">
                <p class="mb-2 text-lg text-muted-foreground">Twój najbardziej prawdopodobny typ to:</p>
                <div v-if="topType" class="inline-block rounded-full bg-primary px-6 py-3 text-4xl font-black text-primary-foreground">
                    Typ {{ topType.type }}
                </div>
                <div v-else class="text-xl font-bold text-red-500">Brak wyników</div>
            </div>

            <div v-if="!stage1Results?.isUnresolvable" class="mb-8 border-t pt-6 text-left">
                <h3 class="mb-4 text-xl font-bold text-primary-foreground">Kolejność instynktów:</h3>
                <div class="flex items-center justify-around text-2xl font-bold uppercase">
                    <div>{{ stage1Results?.dominant }}</div>
                    <div>/</div>
                    <div>{{ stage1Results?.secondary }}</div>
                    <div>/</div>
                    <div>{{ stage1Results?.weakest }}</div>
                </div>
            </div>

            <div v-if="debug" class="mt-12 rounded border-t border-dashed bg-muted/40 p-4 pt-6 text-left">
                <h3 class="mb-4 text-lg font-bold text-primary-foreground">Dane Debugowania:</h3>

                <div class="mb-6">
                    <h4 class="mb-2 text-sm font-bold text-foreground uppercase">Etap 1 (Instynkty):</h4>
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <p class="font-semibold">Część 1:</p>
                            <pre>{{ stage1Results?.scoresPart1 }}</pre>
                        </div>
                        <div>
                            <p class="font-semibold">Część 2:</p>
                            <pre>{{ stage1Results?.scoresPart2 }}</pre>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="mb-2 text-sm font-bold text-foreground uppercase">Etap 2 (Typy):</h4>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div
                            v-for="item in sortedTypes"
                            :key="item.type"
                            :class="{ 'bg-secondary': item.score === maxScore, 'bg-background': item.score == 0 }"
                            class="rounded border border-muted-foreground p-1"
                        >
                            Typ {{ item.type }}: <strong>{{ item.score }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <button class="rounded bg-secondary px-6 py-2 text-secondary-foreground transition hover:opacity-90" @click="restart">
                    Zacznij od nowa
                </button>
            </div>
        </div>
    </div>
</template>
