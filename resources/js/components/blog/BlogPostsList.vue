<script lang="ts" setup>
import type { Pagination, PostItem } from '@/types/blog.types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    posts: PostItem[];
    blogSlug: string;
    pagination?: Pagination | null;
}>();

const { t } = useI18n();

// Translation helper
function translatePaginationLabel(rawLabel: string): string {
    // Strip HTML entities like &laquo; and &raquo; and trim
    const cleanedLabel = rawLabel
        .replace(/&laquo;|«/g, '')
        .replace(/&raquo;|»/g, '')
        .trim();

    // If numeric (page number) return as-is
    if (/^\d+$/.test(cleanedLabel)) {
        return cleanedLabel;
    }

    // Laravel may return 'Previous' or 'Next'
    const lowerCaseLabel = cleanedLabel.toLowerCase();
    if (lowerCaseLabel === 'previous') {
        return t('blog.pagination.previous');
    }
    if (lowerCaseLabel === 'next') {
        return t('blog.pagination.next');
    }

    // Fallback to original text
    return cleanedLabel;
}

// Computed properties
const paginationLinks = computed(() => props.pagination?.links ?? []);
const hasPosts = computed(() => props.posts && props.posts.length > 0);
const hasPagination = computed(() => paginationLinks.value.length > 0);

// CSS classes
const LINK_BASE_CLASSES = 'rounded border px-2 py-1 text-sm transition-colors';
const LINK_ACTIVE_CLASSES = 'border-foreground text-foreground hover:bg-muted';
const LINK_INACTIVE_CLASSES = 'border-border text-muted-foreground';
const LINK_HOVER_CLASSES = 'hover:bg-muted';
const LINK_DISABLED_CLASSES = 'pointer-events-none opacity-50';

function getPaginationLinkClasses(link: { active: boolean; url: string | null }): string[] {
    const classes = [LINK_BASE_CLASSES];

    if (link.active) {
        classes.push(LINK_ACTIVE_CLASSES);
    } else {
        classes.push(LINK_INACTIVE_CLASSES);
    }

    if (!link.url) {
        classes.push(LINK_DISABLED_CLASSES);
    } else {
        classes.push(LINK_HOVER_CLASSES);
    }

    return classes;
}
</script>

<template>
    <section :aria-label="t('blog.posts_list.aria')" :style="{ fontFamily: 'var(--blog-body-font)' }">
        <h2 :style="{ fontFamily: 'var(--blog-header-font)' }" class="mb-2 text-xl font-semibold text-foreground opacity-90">
            {{ t('blog.posts_list.title') }}
        </h2>

        <p v-if="!hasPosts">
            {{ t('blog.posts_list.empty') }}
        </p>

        <ul v-else class="space-y-3">
            <li v-for="post in posts" :key="post.id">
                <Link
                    :href="route('blog.public.post', { blog: blogSlug, postSlug: post.slug })"
                    class="font-semibold text-link hover:text-link-hover hover:underline"
                >
                    {{ post.title }}
                </Link>
                <small v-if="post.published_at" class="text-muted-foreground"> · {{ post.published_at }} </small>
                <div v-if="post.excerpt" class="prose -mt-3 max-w-none text-secondary-foreground opacity-90" v-html="post.excerpt" />
            </li>
        </ul>

        <nav v-if="hasPagination" :aria-label="t('blog.pagination.aria')" class="mt-4 flex items-center gap-1">
            <Link
                v-for="(link, index) in paginationLinks"
                :key="index"
                :class="getPaginationLinkClasses(link)"
                :href="link.url || ''"
                preserve-scroll
            >
                <span>{{ translatePaginationLabel(String(link.label)) }}</span>
            </Link>
        </nav>
    </section>
</template>
