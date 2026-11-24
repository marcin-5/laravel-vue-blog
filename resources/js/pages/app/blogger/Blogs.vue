<script lang="ts" setup>
import BloggerManagementPanel from '@/components/blogger/BloggerManagementPanel.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import BlogCreateSection from '@/pages/app/blogger/components/BlogCreateSection.vue';
import BlogList from '@/pages/app/blogger/components/BlogList.vue';
import BlogVerificationList from '@/components/admin/BlogVerificationList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BlogVerificationRow } from '@/types/admin.types';
import type { BreadcrumbItem } from '@/types';
import type { AdminBlog as Blog, Category } from '@/types/blog.types';
import { ChevronDown } from '@lucide/vue';
import { shallowRef } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blogs: Blog[];
    canCreate: boolean;
    categories: Category[];
    adminBlogs?: BlogVerificationRow[];
}>();
const { t } = useI18n();
const selectedView = shallowRef<'blogger' | 'admin'>('blogger');

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.index'), href: '/blogs' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header-actions>
            <DropdownMenu v-if="props.adminBlogs">
                <DropdownMenuTrigger as-child>
                    <Button class="h-8 gap-1 px-2 font-normal" size="sm" variant="ghost">
                        <span class="text-muted-foreground">
                            {{ selectedView === 'admin' ? t('dashboard.view_switcher.admin') : t('dashboard.view_switcher.blogger') }}
                        </span>
                        <ChevronDown class="h-4 w-4 text-muted-foreground" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" class="w-48">
                    <DropdownMenuItem :class="{ 'bg-accent': selectedView === 'blogger' }" @click="selectedView = 'blogger'">
                        {{ t('dashboard.view_switcher.blogger') }}
                    </DropdownMenuItem>
                    <DropdownMenuItem :class="{ 'bg-accent': selectedView === 'admin' }" @click="selectedView = 'admin'">
                        {{ t('dashboard.view_switcher.admin') }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </template>
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <template v-if="selectedView === 'blogger'">
                <BloggerManagementPanel :can-create="props.canCreate" :items="props.blogs">
                    <template #create="{ isOpen, open, close }">
                        <BlogCreateSection
                            :can-create="props.canCreate"
                            :categories="props.categories"
                            :close="close"
                            :open="open"
                            :show-create="isOpen"
                        />
                    </template>
                    <template #list="{ items, canCreate, requestCreate }">
                        <BlogList :blogs="items" :can-create="canCreate" :categories="props.categories" @create="requestCreate" />
                    </template>
                </BloggerManagementPanel>
            </template>
            <BlogVerificationList v-else-if="props.adminBlogs" :blogs="props.adminBlogs" />
        </div>
    </AppLayout>
</template>
