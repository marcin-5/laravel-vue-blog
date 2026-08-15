<script lang="ts" setup>
import BlogBreadcrumbs from '@/components/blog/BlogBreadcrumbs.vue';
import BlogFooter from '@/components/blog/BlogFooter.vue';
import BlogLayout from '@/components/blog/BlogLayout.vue';
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import type { Blog, BlogChrome, PostListing } from '@/types/blog.types';
import { hasContent } from '@/utils/stringUtils';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        blog: Blog;
        chrome: BlogChrome;
        listing?: PostListing | null;
        contentSpacingClass?: string;
        middleDividerClass?: string;
    }>(),
    {
        listing: null,
        contentSpacingClass: '',
        middleDividerClass: 'my-4',
    },
);

const breadcrumbs = computed(() => props.chrome.navigation?.breadcrumbs ?? []);
const hasFooterContent = computed(() => hasContent(props.chrome.footerHtml));
</script>

<template>
    <BlogLayout v-if="blog" :isPublic="true" :sidebar="chrome.sidebar" :theme="blog.theme">
        <template #top-divider>
            <BorderDivider class="mb-4" />
        </template>

        <template #header>
            <slot name="header"></slot>
        </template>

        <template #content>
            <slot name="content"></slot>
        </template>

        <template #middle-divider>
            <BorderDivider :class="middleDividerClass" />
        </template>

        <template #breadcrumbs>
            <BlogBreadcrumbs :breadcrumbs="breadcrumbs" />
        </template>

        <template #sidebar-content>
            <slot name="sidebar-content">
                <BlogPostsList
                    v-if="listing"
                    id="posts-list"
                    :activeTag="listing.activeTag"
                    :allTags="listing.allTags"
                    :blogId="blog.id"
                    :blogSlug="blog.slug"
                    :class="contentSpacingClass"
                    :mainDomain="blog.main_domain ?? undefined"
                    :pagination="listing.pagination"
                    :posts="listing.posts"
                />
            </slot>
        </template>

        <template #navigation>
            <slot name="navigation">
                <BlogPostNav v-if="listing" :activeTag="listing.activeTag" :navigation="chrome.navigation" />
            </slot>
        </template>

        <template #footer>
            <template v-if="hasFooterContent">
                <BorderDivider class="my-4" />
                <BlogFooter :html="chrome.footerHtml || ''" />
            </template>
        </template>
    </BlogLayout>
</template>
