<script lang="ts" setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    group: {
        id: number;
        name: string;
        slug: string;
        content: string | null;
        footer: string | null;
    };
    posts: Array<{
        id: number;
        title: string;
        slug: string;
        excerpt: string | null;
        published_at: string | null;
    }>;
    pagination: {
        current_page: number;
        last_page: number;
        total: number;
    };
    theme: any;
    sidebar: number;
}>();

const themeStyle = computed(() => {
    if (!props.theme) return {};
    // Uproszczone mapowanie motywu, podobnie jak w useBlogTheme
    return {
        '--blog-bg': props.theme.background || '',
        '--blog-text': props.theme.text || '',
    };
});
</script>

<template>
    <Head :title="group.name" />

    <div :style="themeStyle" class="min-h-screen bg-background p-6 text-foreground">
        <div class="mx-auto max-w-4xl">
            <header class="mb-8 border-b pb-4">
                <h1 class="text-4xl font-bold">{{ group.name }}</h1>
                <div v-if="group.content" class="prose dark:prose-invert mt-4" v-html="group.content"></div>
            </header>

            <main>
                <h2 class="mb-4 text-2xl font-semibold">Posty w grupie</h2>
                <div v-if="posts.length > 0" class="space-y-6">
                    <article v-for="post in posts" :key="post.id" class="rounded-lg border p-4">
                        <h3 class="text-xl font-bold">
                            <Link :href="route('group.post', { group: group.slug, postSlug: post.slug })" class="hover:underline">
                                {{ post.title }}
                            </Link>
                        </h3>
                        <p v-if="post.excerpt" class="mt-2 text-muted-foreground">{{ post.excerpt }}</p>
                        <div class="mt-2 text-xs text-muted-foreground">
                            {{ post.published_at }}
                        </div>
                    </article>
                </div>
                <div v-else class="text-muted-foreground italic">Brak postów w tej grupie.</div>
            </main>

            <footer v-if="group.footer" class="mt-12 border-t pt-8 text-sm text-muted-foreground" v-html="group.footer"></footer>
        </div>
    </div>
</template>
