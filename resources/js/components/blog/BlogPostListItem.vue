<script lang="ts" setup>
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { PostItem, Tag } from '@/types/blog.types';
import { formatDate } from '@/utils/dateUtils';
import { Link } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    post: PostItem;
    showExcerpts: boolean;
    postHref: string;
    getTagHref: (tag: Tag) => string;
    isTagActive: (tag: Tag) => boolean;
}>();

const { t } = useI18n();

const excerptStyle = {
    fontFamily: 'var(--blog-excerpt-font)',
    fontSize: 'calc(1rem * var(--blog-excerpt-scale))',
    fontWeight: 'var(--blog-excerpt-weight)',
};

function getPostTagLinkClasses(tag: Tag) {
    return ['text-xs font-medium transition-colors hover:text-link', props.isTagActive(tag) ? 'text-link-hover' : 'text-muted-foreground'];
}
</script>

<template>
    <div class="flex flex-col">
        <div class="inline">
            <Link :href="postHref" class="font-semibold text-link hover:text-link-hover hover:underline">
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
                        <button :aria-label="t('blog.posts_list.view_excerpt')" class="text-muted-foreground transition-colors hover:text-primary">
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
</template>
