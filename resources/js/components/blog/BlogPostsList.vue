<script lang="ts" setup>
import { ensureNamespace } from '@/i18n';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
}

const { t, locale } = useI18n();
// Ensure "blog" namespace is loaded for current locale (supports SSR via Suspense)
await ensureNamespace(locale.value, 'blog');

const props = defineProps<{
    posts: PostItem[];
    blogSlug: string;
    pagination?: {
        links: { url: string | null; label: string; active: boolean }[];
        prevUrl: string | null;
        nextUrl: string | null;
    } | null;
}>();

function translateLabel(raw: string): string {
    // Strip HTML entities like &laquo; and &raquo; and trim
    const txt = raw
        .replace(/&laquo;|«/g, '')
        .replace(/&raquo;|»/g, '')
        .trim();
    // If numeric (page number) return as-is
    if (/^\d+$/.test(txt)) return txt;
    // Laravel may return 'Previous' or 'Next'
    const lower = txt.toLowerCase();
    if (lower === 'previous') return t('blog.pagination.previous');
    if (lower === 'next') return t('blog.pagination.next');
    // Fallback to original text
    return txt;
}

const links = computed(() => props.pagination?.links ?? []);
</script>

<template>
    <section :aria-label="t('blog.posts_list.aria')">
        <h2 class="mb-2 text-xl font-semibold text-slate-700 dark:text-slate-500">{{ t('blog.posts_list.title') }}</h2>
        <p v-if="!posts || posts.length === 0">{{ t('blog.posts_list.empty') }}</p>
        <ul v-else class="space-y-3">
            <li v-for="p in posts" :key="p.id">
                <Link
                    :href="route('blog.public.post', { blog: blogSlug, postSlug: p.slug })"
                    class="font-semibold text-teal-900 hover:underline dark:font-normal dark:text-sky-200"
                >
                    {{ p.title }}
                </Link>
                <small v-if="p.published_at" class="text-gray-700 dark:text-gray-400"> · {{ p.published_at }}</small>
                <div v-if="p.excerpt" class="prose dark:prose-invert -mt-3 max-w-none text-slate-800 dark:text-slate-400" v-html="p.excerpt"></div>
            </li>
        </ul>

        <nav v-if="links.length" :aria-label="t('blog.pagination.aria')" class="mt-4 flex items-center gap-1">
            <Link
                v-for="(lnk, idx) in links"
                :key="idx"
                :class="[
                    'rounded border px-2 py-1 text-sm',
                    lnk.active
                        ? 'border-slate-500 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800'
                        : 'border border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-600',
                    !lnk.url ? 'pointer-events-none opacity-50' : 'hover:bg-gray-50 dark:hover:bg-gray-800',
                ]"
                :href="lnk.url || ''"
                preserve-scroll
            >
                <span>{{ translateLabel(String(lnk.label)) }}</span>
            </Link>
        </nav>
    </section>
</template>
