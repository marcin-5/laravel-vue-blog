<script lang="ts" setup>
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { enneagramThemes, type EnneagramTheme } from '../composables/useEnneagramPreferences';

const props = defineProps<{
    theme: EnneagramTheme;
    localeLabel: string;
    processing: boolean;
    hasSession: boolean;
}>();

const emit = defineEmits<{
    theme: [value: EnneagramTheme];
    reset: [];
}>();

const { t } = useI18n();

function selectTheme(value: unknown): void {
    if (typeof value === 'string' && enneagramThemes.includes(value as EnneagramTheme)) {
        emit('theme', value as EnneagramTheme);
    }
}
</script>

<template>
    <header class="mx-auto mb-6 flex max-w-4xl flex-wrap items-center justify-between gap-3">
        <label class="flex items-center gap-3 text-sm font-medium text-foreground">
            <span>{{ t('theme_selection') }}</span>
            <Select :model-value="props.theme" @update:model-value="selectTheme">
                <SelectTrigger class="w-44">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="theme in enneagramThemes" :key="theme" :value="theme">
                        {{ t(`themes.${theme.replace('theme-', '')}`) }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </label>
        <div class="flex items-center gap-3">
            <span class="rounded-full border px-3 py-1 text-xs font-semibold" aria-label="Current language">
                {{ props.localeLabel }}
            </span>
            <Button v-if="props.hasSession" :disabled="props.processing" variant="ghost" @click="emit('reset')">
                {{ t('restart') }}
            </Button>
        </div>
    </header>
</template>