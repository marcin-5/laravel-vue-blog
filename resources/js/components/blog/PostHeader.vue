<script lang="ts" setup>
import ViewStats from '@/components/blog/ViewStats.vue';
import type { PostDetails } from '@/types/blog.types';
import { formatDate, shouldShowUpdatedDate } from '@/utils/dateUtils';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    post: PostDetails;
    viewStats: {
        total: number;
        unique?: number;
        anonymous: number;
        bots: number;
        registered: number;
    } | null;
    locale?: string;
    publishedTime?: string | null;
    modifiedTime?: string | null;
}>();

const { t } = useI18n();

const authorLabel = computed(() => t('blog.post.author', ''));
const publishedLabel = computed(() => t('blog.post.published', 'Published:'));
const updatedLabel = computed(() => t('blog.post.updated', 'Updated:'));

const showUpdated = computed(() => shouldShowUpdatedDate(props.publishedTime, props.modifiedTime));
const formattedUpdatedDate = computed(() => formatDate(props.modifiedTime, props.locale));
</script>

<template>
    <header :style="{ fontFamily: 'var(--blog-header-font)', fontSize: 'calc(1.5rem * var(--blog-header-scale))' }" class="mb-4">
        <ViewStats
            v-if="viewStats"
            :anonymous="viewStats.anonymous"
            :bots="viewStats.bots"
            :registered="viewStats.registered"
            class="mb-8 justify-end"
        />
        <h1 class="font-[inherit] text-[1em] leading-tight font-bold text-foreground">{{ post.title }}</h1>
        <div class="my-2 inline-flex items-center gap-x-5 text-sm font-medium text-muted-foreground">
            <p v-if="post.published_at" class="italic">{{ publishedLabel }} {{ formatDate(post.published_at) }}</p>
        </div>
        <p v-if="showUpdated" class="-mt-1 mb-2 text-xs text-muted-foreground italic">{{ updatedLabel }} {{ formattedUpdatedDate }}</p>
        <p
            v-if="post.author"
            :style="{ fontFamily: 'var(--blog-footer-font)', fontSize: 'calc(1rem * var(--blog-body-scale))' }"
            class="text-primary"
        >
            {{ authorLabel }}
            <a :href="`mailto:${post.author_email}`">{{ post.author }}</a>
        </p>
    </header>
</template>
