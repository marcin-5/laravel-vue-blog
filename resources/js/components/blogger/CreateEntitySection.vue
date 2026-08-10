<script generic="T extends Record<string, any>" lang="ts" setup>
import CreateSection from '@/components/blogger/CreateSection.vue';

interface Props {
    canCreate: boolean;
    title: string;
    tooltipCreate: string;
    tooltipClose?: string;
    tooltipLimit: string;
    form?: T;
}

const props = defineProps<Props>();
const showCreate = defineModel<boolean>('showCreate', { required: true });

defineSlots<{
    form: (props: { form?: T; onCancel: () => void; onSubmit: (form: T) => void }) => unknown;
}>();

const emit = defineEmits<{
    submit: [form: T];
    cancel: [];
}>();

function handleCancel(): void {
    emit('cancel');
}

function handleSubmit(form: T): void {
    emit('submit', form);
}
</script>

<template>
    <CreateSection
        v-model:show-create="showCreate"
        :can-create="props.canCreate"
        :title="props.title"
        :tooltip-close="props.tooltipClose"
        :tooltip-create="props.tooltipCreate"
        :tooltip-limit="props.tooltipLimit"
    >
        <template #form>
            <slot :form="props.form" :on-cancel="handleCancel" :on-submit="handleSubmit" name="form" />
        </template>
    </CreateSection>
</template>
