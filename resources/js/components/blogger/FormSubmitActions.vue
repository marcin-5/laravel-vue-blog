<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { computed } from 'vue';

interface SubmitTranslations {
    cancel: string;
    create: string;
    save: string;
    apply: string;
    creating: string;
    saving: string;
}

interface Props {
    isEdit: boolean;
    isProcessing: boolean;
    translations: SubmitTranslations;
}

interface Emits {
    (e: 'submit'): void;
    (e: 'apply'): void;
    (e: 'cancel'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const submitButtonLabel = computed(() => {
    if (props.isProcessing) {
        return props.isEdit ? props.translations.saving : props.translations.creating;
    }
    return props.isEdit ? props.translations.save : props.translations.create;
});
</script>

<template>
    <div class="flex items-center gap-2">
        <Button :disabled="isProcessing" type="submit" variant="constructive">
            {{ submitButtonLabel }}
        </Button>
        <Button v-if="isEdit" :disabled="isProcessing" type="button" variant="outline" @click="emit('apply')">
            {{ translations.apply }}
        </Button>
        <Button type="button" variant="destructive" @click="emit('cancel')">
            {{ translations.cancel }}
        </Button>
    </div>
</template>
