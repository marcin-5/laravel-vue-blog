<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';

const { appearance, updateAppearance } = useAppearance();
const isSystemDark = useMediaQuery('(prefers-color-scheme: dark)');
const isDarkMode = computed(() => {
    if (appearance.value === 'system') {
        return isSystemDark.value;
    }
    return appearance.value === 'dark';
});

function toggleTheme() {
    updateAppearance(isDarkMode.value ? 'light' : 'dark');
}
</script>

<template>
    <Button class="mr-4 flex items-center gap-2" type="button" variant="toggle" @click="toggleTheme">
        <SunIcon v-if="isDarkMode" class="h-4 w-4" />
        <MoonIcon v-else class="h-4 w-4" />
    </Button>
</template>
