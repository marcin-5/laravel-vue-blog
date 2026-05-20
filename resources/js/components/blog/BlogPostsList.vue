<script lang="ts" setup>
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useBlogExcerpts } from '@/composables/useBlogExcerpts';
import type { PostItem } from '@/types/blog.types';
import { formatDate } from '@/utils/dateUtils';
import { Link } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Pagination } from '@/types';

const props = defineProps<{
    posts: PostItem[];
    blogSlug: string;
    blogId: number;
    pagination?: Pagination | null;
    isGroup?: boolean;
    activeTag?: {
        id: number;
        name: string;
        slug: string;
    } | null;
}>();

const { t } = useI18n();
const { showExcerpts } = useBlogExcerpts(props.blogSlug);

const postUrlRoute = computed(() => (props.isGroup ? 'group.post' : 'blog.public.post'));
const postUrlParams = (post: PostItem) =>
    props.isGroup ? { group: props.blogSlug, postSlug: post.slug } : { blog: props.blogSlug, postSlug: post.slug };

const paginationLabelsMap: Record<string, string> = {
    previous: 'blog.pagination.previous',
    next: 'blog.pagination.next',
};

function translatePaginationLabel(rawLabel: string): string {
    const cleaned = rawLabel.replace(/[«»]|&[lr]aquo;/g, '').trim();
    return paginationLabelsMap[cleaned.toLowerCase()] ? t(paginationLabelsMap[cleaned.toLowerCase()]) : cleaned;
}

const paginationLinks = computed(() => props.pagination?.links ?? []);
const hasPosts = computed(() => props.posts.length > 0);
const hasPagination = computed(() => paginationLinks.value.length > 0);

function getPaginationLinkClasses(link: { active: boolean; url: string | null }) {
    return [
        'rounded border px-2 py-1 text-sm text-primary transition-colors',
        link.active ? 'border-foreground bg-background' : 'border-border bg-card',
        link.url ? 'hover:bg-secondary' : 'pointer-events-none opacity-50',
    ];
}
</script>

<template>
    <section
        :aria-label="t('blog.posts_list.aria')"
        :style="{ fontFamily: 'var(--blog-body-font)', fontSize: 'calc(1rem * var(--blog-body-scale))' }"
    >
        <div class="mb-4 flex items-center justify-between gap-4">
            <h2 :style="{ fontFamily: 'var(--blog-header-font)' }" class="text-xl font-semibold text-primary opacity-90">
                {{ t('blog.posts_list.title') }}
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ t('blog.posts_list.show_excerpts') }}</span>
                <Switch v-model="showExcerpts" />
            </div>
        </div>

        <div v-if="activeTag" class="mb-4 flex items-center gap-2">
            <span class="text-sm text-muted-foreground">
                {{ t('blog.posts_list.active_tag') }}:
                <span class="font-semibold text-primary">{{ activeTag.name }}</span>
            </span>
            <Link :href="route('blog.public.landing', { blog: blogSlug })" class="text-xs text-link hover:underline">
                {{ t('blog.posts_list.clear_filter') }}
            </Link>
        </div>

        <p v-if="!hasPosts">
            {{ t('blog.posts_list.empty') }}
        </p>

        <ul v-else class="space-y-3">
            <li v-for="post in posts" :key="post.id">
                <div class="flex flex-col">
                    <div class="inline">
                        <Link :href="route(postUrlRoute, postUrlParams(post))" class="font-semibold text-link hover:text-link-hover hover:underline">
                            {{ post.title }}
                        </Link>
                        <small v-if="showExcerpts && post.published_at" class="text-muted-foreground"
                            ><span class="font-black"> · </span>{{ formatDate(post.published_at) }}
                        </small>
                    </div>

                    <div v-if="post.tags && post.tags.length > 0" class="mb-1 flex flex-wrap gap-x-2 gap-y-1">
                        <Link
                            v-for="tag in post.tags"
                            :key="tag.id"
                            :href="route('blog.public.tag', { blog: blogSlug, tag: tag.slug })"
                            class="text-xs font-medium text-muted-foreground transition-colors hover:text-link"
                        >
                            #{{ tag.name }}
                        </Link>
                    </div>

                    <div v-if="!showExcerpts" class="flex items-center gap-2">
                        <small v-if="post.published_at" class="text-muted-foreground">
                            {{ formatDate(post.published_at) }}
                        </small>
                        <TooltipProvider v-if="post.excerpt">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <button
                                        :aria-label="t('blog.posts_list.view_excerpt')"
                                        class="text-muted-foreground transition-colors hover:text-primary"
                                    >
                                        <Info class="size-4" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent class="max-w-md bg-secondary p-3">
                                    <div class="prose prose-sm prose-invert -my-5" v-html="post.excerpt" />
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>

                    <div
                        v-if="showExcerpts && post.excerpt"
                        :style="{
                            fontFamily: 'var(--blog-excerpt-font)',
                            fontSize: 'calc(1rem * var(--blog-excerpt-scale))',
                            fontWeight: 'var(--blog-excerpt-weight)',
                        }"
                        class="my-1 max-w-none text-secondary-foreground opacity-90"
                        v-html="post.excerpt"
                    />
                </div>
            </li>
        </ul>

        <!-- Newsletter link -->
        <div v-if="!isGroup" class="mt-4 flex">
            <Link :href="route('newsletter.index', { blog_id: blogId })" class="text-sm font-medium text-link-hover hover:text-primary">
                {{ t('blog.posts_list.newsletter_subscribe') }}
            </Link>
        </div>

        <nav
            v-if="hasPagination"
            :aria-label="t('blog.pagination.aria')"
            :style="{ fontFamily: 'var(--blog-nav-font)' }"
            class="mt-4 flex items-center gap-1"
        >
            <Link
                v-for="(link, index) in paginationLinks"
                :key="index"
                :class="getPaginationLinkClasses(link)"
                :href="link.url || ''"
                preserve-scroll
            >
                {{ translatePaginationLabel(String(link.label)) }}
            </Link>
        </nav>
    </section>
</template>
