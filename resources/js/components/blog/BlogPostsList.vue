<script lang="ts" setup>
import BlogPostListItem from '@/components/blog/BlogPostListItem.vue';
import BlogPostsPagination from '@/components/blog/BlogPostsPagination.vue';
import { Switch } from '@/components/ui/switch';
import { useBlogExcerpts } from '@/composables/useBlogExcerpts';
import { useBlogPostsListLinks, type ActiveTag, type TagOperator } from '@/composables/useBlogPostsListLinks';
import type { PostItem, Tag } from '@/types/blog.types';
import type { Pagination } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const TAG_OPERATORS: TagOperator[] = ['AND', 'OR'];

const props = defineProps<{
    posts: PostItem[];
    blogSlug: string;
    mainDomain?: string;
    blogId: number;
    pagination?: Pagination | null;
    isGroup?: boolean;
    activeTag?: ActiveTag | null;
    activeTags?: ActiveTag[];
    operator?: TagOperator;
    allowMultipleTagFiltering?: boolean;
    allTags?: Tag[];
}>();

const { t } = useI18n();
const { showExcerpts } = useBlogExcerpts(props.blogSlug);

const sectionStyle = {
    fontFamily: 'var(--blog-body-font)',
    fontSize: 'calc(1rem * var(--blog-body-scale))',
};

const headerStyle = {
    fontFamily: 'var(--blog-header-font)',
};

const tagLinkBaseClasses = 'rounded-full border px-2 py-0.5 text-sm font-medium transition-colors';
const activeTagLinkClasses = 'border-link bg-link/10 text-foreground';
const inactiveTagLinkClasses = 'border-border bg-card text-muted-foreground hover:border-link hover:text-link';

const paginationLinks = computed(() => props.pagination?.links ?? []);
const hasPosts = computed(() => props.posts.length > 0);
const hasPagination = computed(() => paginationLinks.value.length > 0);
const hasTags = computed(() => Boolean(props.allTags?.length));

const {
    anchorId,
    selectedTags,
    activeOperator,
    isMultiTagMode,
    getPostHref,
    getTagHref,
    getBlogLandingHref,
    getMultiTagFilterHref,
    getNewsletterHref,
} = useBlogPostsListLinks(props);
const postsListTitle = computed(() => {
    if (props.isGroup) {
        return props.activeTag ? t('group.posts_list.active_tag') : t('group.posts_list.title');
    }

    if (selectedTags.value.length === 1) {
        return t('blog.posts_list.active_tag');
    }

    return selectedTags.value.length > 1 ? t('blog.posts_list.active_tags') : t('blog.posts_list.title');
});
const selectedTagNames = computed(() => {
    const names = selectedTags.value.map((tag) => tag.name);
    const conjunction = activeOperator.value === 'AND' ? t('blog.posts_list.tag_conjunction_and') : t('blog.posts_list.tag_conjunction_or');

    if (names.length < 2) {
        return names.join('');
    }

    return `${names.slice(0, -1).join(', ')}${conjunction}${names[names.length - 1]}`;
});

function setTagOperator(operator: TagOperator): void {
    const href = getMultiTagFilterHref(
        selectedTags.value.map((tag) => tag.slug),
        operator,
        anchorId.value,
    );

    router.visit(href, { preserveScroll: true });
}

function isTagActive(tag: Tag): boolean {
    return selectedTags.value.some((activeTag) => activeTag.id === tag.id);
}

function getTagLinkClasses(tag: Tag) {
    return [tagLinkBaseClasses, isTagActive(tag) ? activeTagLinkClasses : inactiveTagLinkClasses];
}
</script>

<template>
    <section :id="anchorId.slice(1)" :aria-label="t('blog.posts_list.aria')" :style="sectionStyle">
        <div class="mb-4 flex items-center justify-between gap-4">
            <h2 :style="headerStyle" class="text-xl font-semibold text-primary opacity-90">
                {{ postsListTitle }}:
                <span v-if="selectedTags.length > 0" class="font-semibold text-primary-foreground">
                    {{ selectedTagNames }}
                </span>
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
                    v-if="selectedTags.length > 0"
                    :class="[tagLinkBaseClasses, 'border-link bg-link-hover/10 text-link-hover']"
                    :href="getBlogLandingHref()"
                    preserve-scroll
                >
                    {{ t('blog.posts_list.clear_filter') }}
                </Link>
                <div
                    v-if="isMultiTagMode && selectedTags.length >= 2"
                    :aria-label="t('blog.posts_list.operator_label')"
                    class="ml-auto flex shrink-0 flex-nowrap items-center gap-2"
                    role="group"
                >
                    <button
                        v-for="tagOperator in TAG_OPERATORS"
                        :key="tagOperator"
                        :aria-pressed="activeOperator === tagOperator"
                        :class="activeOperator === tagOperator ? activeTagLinkClasses : inactiveTagLinkClasses"
                        class="rounded-full border px-2 py-0.5 text-xs font-medium transition-colors"
                        type="button"
                        @click="setTagOperator(tagOperator)"
                    >
                        {{ t(`blog.posts_list.operator_${tagOperator.toLowerCase()}`) }}
                    </button>
                </div>
            </div>
        </div>

        <p v-if="!hasPosts">
            {{ t('blog.posts_list.empty') }}
        </p>

        <ul v-else class="space-y-3">
            <li v-for="post in posts" :key="post.id">
                <BlogPostListItem
                    :post="post"
                    :show-excerpts="showExcerpts"
                    :post-href="getPostHref(post)"
                    :get-tag-href="getTagHref"
                    :is-tag-active="isTagActive"
                />
            </li>
        </ul>

        <BlogPostsPagination v-if="hasPagination" :links="paginationLinks" />

        <div v-if="!isGroup" class="mt-4">
            <Link :href="getNewsletterHref()" class="inline-flex items-center gap-1.5 text-sm font-medium text-link-hover hover:text-primary">
                <Mail class="h-4 w-4 shrink-0" />
                <span>{{ t('blog.posts_list.newsletter_subscribe') }}</span>
            </Link>
        </div>
    </section>
</template>
