<script lang="ts" setup>
import BlogVerificationRow from '@/components/admin/BlogVerificationRow.vue';
import type { BlogVerificationRow as BlogVerificationRowType } from '@/types/admin.types';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blogs: BlogVerificationRowType[];
}>();

const { t } = useI18n();
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4">
        <h2 class="mb-3 text-lg font-semibold">{{ t('blogger.verification.title') }}</h2>
        <p v-if="props.blogs.length === 0" class="text-sm text-muted-foreground">
            {{ t('blogger.verification.empty') }}
        </p>
        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-sidebar-border/70 text-xs text-muted-foreground uppercase">
                    <tr>
                        <th>{{ t('blogger.verification.blog') }}</th>
                        <th>{{ t('blogger.verification.owner') }}</th>
                        <th>{{ t('blogger.verification.token') }}</th>
                        <th>{{ t('blogger.verification.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <BlogVerificationRow v-for="blog in props.blogs" :key="blog.id" :blog="blog" />
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
@reference "../../../css/app.css";

thead th {
    @apply py-2 pr-4;
}
</style>
