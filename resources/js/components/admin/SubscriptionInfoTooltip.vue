<script lang="ts" setup>
import InfoTooltip from '@/components/app/InfoTooltip.vue';

interface Props {
    subscriptions: {
        blog: string;
        frequency: string;
    }[];
}

defineProps<Props>();
</script>

<template>
    <InfoTooltip :columns="['Blog', 'Częstotliwość']" :rows="subscriptions" title="Zasubskrybowane blogi" />
</template>
