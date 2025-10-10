<script lang="ts" setup>
import '@fontsource/cinzel-decorative/900.css';
import '@fontsource/cinzel/700.css';

withDefaults(
    defineProps<{
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        size: 'lg',
    },
);
</script>

<template>
    <h1
        :class="[
            'inline-block p-2 leading-none font-black tracking-tight text-slate-800 dark:text-slate-200',
            size === 'sm' && 'text-xl sm:text-2xl',
            size === 'md' && 'text-3xl sm:text-4xl',
            size === 'lg' && 'text-4xl sm:text-6xl md:text-7xl lg:text-8xl xl:text-9xl',
        ]"
        style="font-family: 'Cinzel', serif"
    >
        <span
            :class="[size !== 'lg' ? 'text-[1.0em]' : 'text-[1.0em]', 'font-black']"
            style="font-family: 'Cinzel Decorative', serif; font-weight: 900"
            >O</span
        >sobliwy
        <span
            :class="[size !== 'lg' ? 'text-[1.0em]' : 'text-[1.2em]', 'font-black']"
            style="font-family: 'Cinzel Decorative', serif; font-weight: 900"
            >B</span
        >log
    </h1>
</template>
