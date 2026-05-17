<script lang="ts" setup>
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
            'font-cinzel inline-block p-2 leading-none font-black tracking-tight text-slate-800 dark:text-slate-200',
            size === 'sm' && 'text-xl sm:text-2xl',
            size === 'md' && 'text-3xl sm:text-4xl',
            size === 'lg' && 'text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl',
        ]"
    >
        <template v-for="(word, index) in words" :key="index">
            <span
                :class="[
                    'font-cinzel-decorative font-[900]',
                    size !== 'lg' ? 'text-[1.0em]' : index !== words.length - 1 ? 'text-[1.0em]' : 'text-[1.2em]',
                ]"
                >{{ word[0] }}</span
            >{{ word.slice(1) }}
            <template v-if="index < words.length - 1">&nbsp;</template>
        </template>
    </h1>
</template>
