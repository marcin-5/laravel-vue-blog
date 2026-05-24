<script lang="ts" setup>
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useBlogExcerpts } from '@/composables/useBlogExcerpts';
import type { PostItem, Tag } from '@/types/blog.types';
import type { Pagination } from '@/types';
import { formatDate } from '@/utils/dateUtils';
import { Link } from '@inertiajs/vue3';
import { Info, Mail } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type ActiveTag = Pick<Tag, 'id' | 'name' | 'slug'>;

type PaginationLink = {
    active: boolean;
    url: string | null;
    label: string | number;
};

const props = defineProps<{
    posts: PostItem[];
    blogSlug: string;
    blogId: number;
    pagination?: Pagination | null;
    isGroup?: boolean;
    activeTag?: ActiveTag | null;
    allTags?: Tag[];
}>();

const { t } = useI18n();
const { showExcerpts } = useBlogExcerpts(props.blogSlug);

const POST_ROUTE_NAMES = {
    group: 'group.post',
    blog: 'blog.public.post',
} as const;

const ROUTE_NAMES = {
    blogLanding: 'blog.public.landing',
    blogTag: 'blog.public.tag',
    newsletter: 'newsletter.index',
} as const;

const PAGINATION_LABEL_TRANSLATIONS: Record<string, string> = {
    previous: 'blog.pagination.previous',
    next: 'blog.pagination.next',
};

const PAGINATION_DECORATION_PATTERN = /[«»]|&[lr]aquo;/g;

const sectionStyle = {
    fontFamily: 'var(--blog-body-font)',
    fontSize: 'calc(1rem * var(--blog-body-scale))',
};

const headerStyle = {
    fontFamily: 'var(--blog-header-font)',
};

const excerptStyle = {
    fontFamily: 'var(--blog-excerpt-font)',
    fontSize: 'calc(1rem * var(--blog-excerpt-scale))',
    fontWeight: 'var(--blog-excerpt-weight)',
};

const navigationStyle = {
    fontFamily: 'var(--blog-nav-font)',
};

const tagLinkBaseClasses = 'rounded-full border px-2 py-0.5 text-xs font-medium transition-colors';
const activeTagLinkClasses = 'border-link bg-link/10 text-link';
const inactiveTagLinkClasses = 'border-border bg-card text-muted-foreground hover:border-link hover:text-link';

const paginationBaseClasses = 'rounded border px-2 py-1 text-sm text-primary transition-colors';
const activePaginationClasses = 'border-foreground bg-background';
const inactivePaginationClasses = 'border-border bg-card';
const enabledPaginationClasses = 'hover:bg-secondary';
const disabledPaginationClasses = 'pointer-events-none opacity-50';

const postRouteName = computed(() => (props.isGroup ? POST_ROUTE_NAMES.group : POST_ROUTE_NAMES.blog));
const paginationLinks = computed(() => props.pagination?.links ?? []);
const hasPosts = computed(() => props.posts.length > 0);
const hasPagination = computed(() => paginationLinks.value.length > 0);
const hasTags = computed(() => Boolean(props.allTags?.length));
const postsListTitle = computed(() => (!props.activeTag ? t('blog.posts_list.title') : t('blog.posts_list.active_tag')));

function getPostRouteParams(post: PostItem) {
    const params: Record<string, any> = props.isGroup
        ? { group: props.blogSlug, postSlug: post.slug }
        : { blog: props.blogSlug, postSlug: post.slug };

    if (props.activeTag) {
        params.tag = props.activeTag.slug;
    }

    return params;
}

function getPostHref(post: PostItem): string {
    return route(postRouteName.value, getPostRouteParams(post));
}

function getBlogLandingHref(): string {
    const anchor = props.isGroup ? '#group-posts-list' : '#posts-list';
    return `${route(ROUTE_NAMES.blogLanding, { blog: props.blogSlug })}${anchor}`;
}

function getTagHref(tag: Tag): string {
    const anchor = props.isGroup ? '#group-posts-list' : '#posts-list';
    return `${route(ROUTE_NAMES.blogTag, { blog: props.blogSlug, tag: tag.slug })}${anchor}`;
}

function getNewsletterHref(): string {
    return route(ROUTE_NAMES.newsletter, { blog_id: props.blogId });
}

function getTagLinkClasses(tag: Tag) {
    return [tagLinkBaseClasses, props.activeTag?.id === tag.id ? activeTagLinkClasses : inactiveTagLinkClasses];
}

function getPostTagLinkClasses(tag: Tag) {
    return ['text-xs font-medium transition-colors hover:text-link', tag.id === props.activeTag?.id ? 'text-link-hover' : 'text-muted-foreground'];
}

function translatePaginationLabel(rawLabel: string): string {
    const cleanedLabel = rawLabel.replace(PAGINATION_DECORATION_PATTERN, '').trim();
    const translationKey = PAGINATION_LABEL_TRANSLATIONS[cleanedLabel.toLowerCase()];

    return translationKey ? t(translationKey) : cleanedLabel;
}

function getPaginationLinkClasses(link: PaginationLink) {
    return [
        paginationBaseClasses,
        link.active ? activePaginationClasses : inactivePaginationClasses,
        link.url ? enabledPaginationClasses : disabledPaginationClasses,
    ];
}
</script>

<template>
    <section :id="props.isGroup ? 'group-posts-list' : 'posts-list'" :aria-label="t('blog.posts_list.aria')" :style="sectionStyle">
        <div class="mb-4 flex items-center justify-between gap-4">
            <h2 :style="headerStyle" class="text-xl font-semibold text-primary opacity-90">
                {{ postsListTitle }}:
                <span v-if="activeTag" class="font-semibold text-primary-foreground">{{ activeTag.name }}</span>
            </h2>

            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ t('blog.posts_list.show_excerpts') }}</span>
                <Switch v-model="showExcerpts" />
            </div>
        </div>

        <div v-if="hasTags" class="mb-4">
            <div class="flex flex-wrap gap-2">
                <Link v-for="tag in allTags" :key="tag.id" :class="getTagLinkClasses(tag)" :href="getTagHref(tag)" preserve-scroll>
                    #{{ tag.name }}
                </Link>
                <Link
                    v-if="activeTag"
                    :class="[tagLinkBaseClasses, 'border-link bg-link-hover/10 text-link-hover']"
                    :href="getBlogLandingHref()"
                    preserve-scroll
                >
                    {{ t('blog.posts_list.clear_filter') }}
                </Link>
            </div>
        </div>

        <p v-if="!hasPosts">
            {{ t('blog.posts_list.empty') }}
        </p>

        <ul v-else class="space-y-3">
            <li v-for="post in posts" :key="post.id">
                <div class="flex flex-col">
                    <div class="inline">
                        <Link :href="getPostHref(post)" class="font-semibold text-link hover:text-link-hover hover:underline">
                            {{ post.title }}
                        </Link>

                        <small v-if="showExcerpts && post.published_at" class="text-muted-foreground">
                            <span class="font-black"> · </span>{{ formatDate(post.published_at) }}
                        </small>
                    </div>

                    <div v-if="post.tags && post.tags.length > 0" class="mb-1 flex flex-wrap gap-x-2 gap-y-1">
                        <Link v-for="tag in post.tags" :key="tag.id" :class="getPostTagLinkClasses(tag)" :href="getTagHref(tag)" preserve-scroll>
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
                        :style="excerptStyle"
                        class="my-1 max-w-none text-secondary-foreground opacity-90"
                        v-html="post.excerpt"
                    />
                </div>
            </li>
        </ul>

        <nav
            v-if="hasPagination"
            :aria-label="t('blog.pagination.aria')"
            :style="navigationStyle"
            class="mt-4 flex items-center justify-center gap-1"
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

        <div v-if="!isGroup" class="mt-4">
            <Link :href="getNewsletterHref()" class="inline-flex items-center gap-1.5 text-sm font-medium text-link-hover hover:text-primary">
                <Mail class="h-4 w-4 shrink-0" />
                <span>{{ t('blog.posts_list.newsletter_subscribe') }}</span>
            </Link>
        </div>
    </section>
</template>
