<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { computed } from 'vue';

interface Props {
    isEdit: boolean;
    isProcessing: boolean;
    isPreviewMode: boolean;
    isFullPreview: boolean;
    previewLayout: 'horizontal' | 'vertical';
    cancelLabel: string;
    createLabel: string;
    saveLabel: string;
    savingLabel: string;
    creatingLabel: string;
    previewLabel: string;
    closePreviewLabel: string;
    fullPreviewLabel: string;
    splitViewLabel: string;
    horizontalLabel: string;
    verticalLabel: string;
}

interface Emits {
    (e: 'submit'): void;
    (e: 'cancel'): void;
    (e: 'togglePreview'): void;
    (e: 'toggleFullPreview'): void;
    (e: 'setLayoutHorizontal'): void;
    (e: 'setLayoutVertical'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const submitButtonLabel = computed(() => {
    if (props.isProcessing) {
        return props.isEdit ? props.savingLabel : props.creatingLabel;
    }
    return props.isEdit ? props.saveLabel : props.createLabel;
});

const previewToggleLabel = computed(() => {
    return props.isPreviewMode ? props.closePreviewLabel : props.previewLabel;
});

const fullPreviewToggleLabel = computed(() => {
    return props.isFullPreview ? props.splitViewLabel : props.fullPreviewLabel;
});
</script>

<template>
    <div class="flex items-center gap-2">
        <!-- Submit and Cancel buttons -->
        <Button :disabled="isProcessing" type="submit" variant="constructive">
            {{ submitButtonLabel }}
        </Button>
        <Button type="button" variant="destructive" @click="emit('cancel')">
            {{ cancelLabel }}
        </Button>

        <!-- Preview controls -->
        <div class="ml-auto flex gap-2">
            <Button :variant="isPreviewMode ? 'exit' : 'toggle'" size="sm" type="button" @click="emit('togglePreview')">
                {{ previewToggleLabel }}
            </Button>
            <Button v-if="isPreviewMode" size="sm" type="button" variant="exit" @click="emit('toggleFullPreview')">
                {{ fullPreviewToggleLabel }}
            </Button>
            <Button v-if="isPreviewMode && !isFullPreview" size="sm" type="button" variant="toggle" @click="emit('setLayoutHorizontal')">
                {{ horizontalLabel }}
            </Button>
            <Button v-if="isPreviewMode && !isFullPreview" size="sm" type="button" variant="toggle" @click="emit('setLayoutVertical')">
                {{ verticalLabel }}
            </Button>
        </div>
    </div>
</template>
