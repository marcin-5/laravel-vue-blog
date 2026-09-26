<script lang="ts" setup>
import PrivateMessageDialog from '@/components/blog/PrivateMessageDialog.vue';
import { Button } from '@/components/ui/button';
import ViewStatsComponent from '@/components/blog/ViewStats.vue';
import type { AppPageProps, SEO } from '@/types';
import type { PostDetails, ViewStats } from '@/types/blog.types';
import { usePage } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';
import { formatDate, shouldShowUpdatedDate } from '@/utils/dateUtils';
import { computed, shallowRef } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    post: PostDetails;
    viewStats?: ViewStats | null;
    locale?: string;
    seo?: Pick<SEO, 'publishedTime' | 'modifiedTime'> | null;
    privateMessageGroupId?: number;
}>();

const { t } = useI18n();
const page = usePage<AppPageProps>();
const dialogOpen = shallowRef(false);

const authorLabel = computed(() => t('blog.post.author', ''));
const publishedLabel = computed(() => t('blog.post.published', 'Published:'));
const updatedLabel = computed(() => t('blog.post.updated', 'Updated:'));

const publishedTime = computed(() => props.seo?.publishedTime ?? null);
const modifiedTime = computed(() => props.seo?.modifiedTime ?? null);

const showUpdated = computed(() => shouldShowUpdatedDate(publishedTime.value, modifiedTime.value));
const formattedUpdatedDate = computed(() => formatDate(modifiedTime.value, props.locale));
const canStartPrivateMessage = computed(() => Boolean(page.props.auth.user && props.post.private_message_url));
</script>

<template>
    <header :style="{ fontFamily: 'var(--blog-header-font)', fontSize: 'calc(2rem * var(--blog-header-scale))' }" class="mb-4">
        <h1 class="mb-2 font-[inherit] text-[1em] leading-tight font-bold text-foreground">{{ post.title }}</h1>
        <ViewStatsComponent
            v-if="viewStats"
            :anonymous="viewStats?.anonymous"
            :bots="viewStats?.bots"
            :consented="viewStats?.consented"
            :markdown="viewStats?.markdown"
        />
        <div class="my-2 inline-flex items-center gap-x-5 text-sm font-medium text-muted-foreground">
            <p v-if="post.published_at" class="italic">{{ publishedLabel }} {{ formatDate(post.published_at) }}</p>
        </div>
        <p v-if="showUpdated" class="-mt-1 mb-2 text-xs text-muted-foreground italic">{{ updatedLabel }} {{ formattedUpdatedDate }}</p>
        <p
            v-if="post.author"
            :style="{ fontFamily: 'var(--blog-footer-font)', fontSize: 'calc(1rem * var(--blog-body-scale))' }"
            class="text-foreground"
        >
            {{ authorLabel }}
            <a :href="`mailto:${post.author_email}`" class="hover:text-primary">{{ post.author }}</a>
            <Button
                v-if="canStartPrivateMessage"
                class="ml-2 h-7 gap-1 px-2 text-xs"
                size="sm"
                type="button"
                variant="outline"
                @click="dialogOpen = true"
            >
                <Mail class="size-3.5" />
                {{ t('private_messaging.actions.contact_owner', 'Private message') }}
            </Button>
        </p>
        <PrivateMessageDialog
            v-if="canStartPrivateMessage"
            v-model:open="dialogOpen"
            :group-id="privateMessageGroupId"
            :post-id="privateMessageGroupId ? undefined : post.id"
            :store-url="post.private_message_url"
        />
    </header>
</template>
