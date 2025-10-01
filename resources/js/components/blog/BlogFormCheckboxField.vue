<script lang="ts" setup>
import InputError from '@/components/InputError.vue';

interface Props {
    id: string;
    label: string;
    modelValue: boolean;
    error?: string;
    additionalInfo?: string;
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function handleChange(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.checked);
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <input :id="props.id" :checked="props.modelValue" type="checkbox" @change="handleChange" />
            <label :for="props.id" class="text-sm">{{ props.label }}</label>
            <span v-if="props.additionalInfo" class="text-xs text-muted-foreground">{{ props.additionalInfo }}</span>
        </div>
        <InputError :message="props.error" />
    </div>
</template>