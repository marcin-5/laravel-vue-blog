<script lang="ts" setup>
import { ensureNamespace } from '@/i18n';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
}

const { t, locale } = useI18n();
// Ensure "landing" namespace is loaded for current locale (supports SSR via Suspense)
await ensureNamespace(locale.value, 'landing');

defineProps<{
    posts: PostItem[];
    blogSlug: string;
}>();
</script>

<template>
    <section :aria-label="t('landing.blog.posts_list.aria')">
        <h2 class="mb-2 text-xl font-semibold text-slate-700 dark:text-slate-500">{{ t('landing.blog.posts_list.title') }}</h2>
        <p v-if="!posts || posts.length === 0">{{ t('landing.blog.posts_list.empty') }}</p>
        <ul v-else class="space-y-3">
            <li v-for="p in posts" :key="p.id">
                <Link
                    :href="route('blog.public.post', { blog: blogSlug, postSlug: p.slug })"
                    class="font-semibold text-teal-800 hover:underline dark:font-normal dark:text-teal-700"
                >
                    {{ p.title }}
                </Link>
                <small v-if="p.published_at" class="text-gray-700 dark:text-gray-400"> · {{ p.published_at }}</small>
                <div v-if="p.excerpt" class="text-slate-800 dark:text-slate-400">{{ p.excerpt }}</div>
            </li>
        </ul>
    </section>
</template>
