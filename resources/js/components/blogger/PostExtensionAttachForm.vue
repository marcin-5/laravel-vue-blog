<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface AvailableExtension {
    id: number;
    title: string;
}

const props = defineProps<{
    availableExtensions: AvailableExtension[];
    selectedExtensionId: string;
    error?: string;
    processing: boolean;
    selectLabel: string;
    placeholder: string;
    addLabel: string;
    noAvailableLabel: string;
}>();

const emit = defineEmits<{
    'update:selectedExtensionId': [value: string];
    submit: [];
}>();
</script>

<template>
    <form class="space-y-3" @submit.prevent="emit('submit')">
        <p v-if="props.availableExtensions.length === 0" class="text-sm text-muted-foreground">
            {{ props.noAvailableLabel }}
        </p>
        <div v-else class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1 space-y-1.5">
                <label class="text-sm font-medium">{{ props.selectLabel }}</label>
                <Select :model-value="props.selectedExtensionId" @update:model-value="emit('update:selectedExtensionId', String($event ?? ''))">
                    <SelectTrigger>
                        <SelectValue :placeholder="props.placeholder" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="extension in props.availableExtensions" :key="extension.id" :value="String(extension.id)">
                            {{ extension.title }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="props.error" />
            </div>
            <Button :disabled="props.processing || !props.selectedExtensionId" type="submit" variant="constructive">
                {{ props.addLabel }}
            </Button>
        </div>
    </form>
</template>
