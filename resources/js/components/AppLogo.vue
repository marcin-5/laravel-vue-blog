<script lang="ts" setup>
import '@fontsource/cinzel-decorative/900.css';
import '@fontsource/cinzel/700.css';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

withDefaults(
    defineProps<{
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        size: 'lg',
    },
);

const { t } = useI18n();
const appName = computed(() => t('common.appName'));
const words = computed(() => appName.value.split(' '));
</script>

<template>
    <h1
        :class="[
            'inline-block p-2 leading-none font-black tracking-tight text-slate-800 dark:text-slate-200',
            size === 'sm' && 'text-xl sm:text-2xl',
            size === 'md' && 'text-3xl sm:text-4xl',
            size === 'lg' && 'text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl',
        ]"
        style="font-family: 'Cinzel', serif"
    >
        <template v-for="(word, index) in words" :key="index">
            <span
                :class="[size !== 'lg' ? 'text-[1.0em]' : index !== words.length - 1 ? 'text-[1.0em]' : 'text-[1.2em]', 'font-black']"
                style="font-family: 'Cinzel Decorative', serif; font-weight: 900"
                >{{ word[0] }}</span
            >{{ word.slice(1) }}
            <template v-if="index < words.length - 1">&nbsp;</template>
        </template>
    </h1>
</template>
