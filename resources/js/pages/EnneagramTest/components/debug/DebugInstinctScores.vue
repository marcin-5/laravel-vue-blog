<script lang="ts" setup>
import type { InstinctScores } from '../../composables/shared/types';

defineProps<{
    scores: InstinctScores;
}>();
</script>

<template>
    <div class="mt-2">
        Wyniki:
        <span class="text-foreground uppercase"> SP={{ scores.sp }}, SO={{ scores.so }}, SX={{ scores.sx }} </span>
    </div>
</template>
