<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    idPrefix: string;
    name: string;
    errors: { name?: string; slug?: string };
    processing: boolean;
    submitLabel: string;
    cancelLabel?: string;
}>();

const emit = defineEmits<{
    'update:name': [value: string];
    submit: [];
    cancel: [];
}>();
</script>

<template>
    <form class="flex flex-col gap-4 sm:flex-row sm:items-end" @submit.prevent="emit('submit')">
        <div class="flex-1 space-y-1.5">
            <label class="text-sm font-medium" :for="`${props.idPrefix}-name`">{{ $t('blogger.tags.name_label') }}</label>
            <Input
                :id="`${props.idPrefix}-name`"
                :model-value="props.name"
                maxlength="100"
                required
                @update:model-value="emit('update:name', String($event))"
            />
            <InputError :message="props.errors.name" />
            <InputError :message="props.errors.slug" />
        </div>
        <div class="flex gap-2">
            <Button :disabled="props.processing" type="submit" variant="constructive">
                {{ props.submitLabel }}
            </Button>
            <Button v-if="props.cancelLabel" :disabled="props.processing" type="button" variant="outline" @click="emit('cancel')">
                {{ props.cancelLabel }}
            </Button>
        </div>
    </form>
</template>
