<script generic="T extends Record<string, any>" lang="ts" setup>
import CreateSection from '@/components/blogger/CreateSection.vue';

defineProps<{
    canCreate: boolean;
    showCreate: boolean;
    title: string;
    tooltipCreate: string;
    tooltipClose?: string;
    tooltipLimit: string;
    form?: any;
}>();

const emit = defineEmits<{
    (e: 'toggle'): void;
    (e: 'submit', form: any): void;
    (e: 'cancel'): void;
}>();
</script>

<template>
    <CreateSection
        :can-create="canCreate"
        :show-create="showCreate"
        :title="title"
        :tooltip-close="tooltipClose"
        :tooltip-create="tooltipCreate"
        :tooltip-limit="tooltipLimit"
        @toggle="emit('toggle')"
    >
        <template #form>
            <slot :form="form" :on-cancel="() => emit('cancel')" :on-submit="(f: any) => emit('submit', f)" name="form" />
        </template>
    </CreateSection>
</template>
