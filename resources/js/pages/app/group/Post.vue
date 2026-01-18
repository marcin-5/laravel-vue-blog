<script lang="ts" setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    group: {
        id: number;
        name: string;
        slug: string;
    };
    post: {
        id: number;
        title: string;
        content: string;
        published_at: string | null;
    };
    theme: any;
    sidebar: number;
}>();

const themeStyle = computed(() => {
    if (!props.theme) return {};
    return {
        '--blog-bg': props.theme.background || '',
        '--blog-text': props.theme.text || '',
    };
});
</script>

<template>
    <Head :title="post.title + ' - ' + group.name" />

    <div :style="themeStyle" class="min-h-screen bg-background p-6 text-foreground">
        <div class="mx-auto max-w-4xl">
            <nav class="mb-8">
                <Link :href="route('group.landing', { group: group.slug })" class="flex items-center gap-2 text-primary hover:underline">
                    <span>&larr;</span> Powrót do grupy {{ group.name }}
                </Link>
            </nav>

            <article>
                <header class="mb-8">
                    <h1 class="text-4xl font-bold">{{ post.title }}</h1>
                    <div v-if="post.published_at" class="mt-2 text-sm text-muted-foreground italic">Opublikowano: {{ post.published_at }}</div>
                </header>

                <div class="prose dark:prose-invert max-w-none" v-html="post.content"></div>
            </article>
        </div>
    </div>
</template>
