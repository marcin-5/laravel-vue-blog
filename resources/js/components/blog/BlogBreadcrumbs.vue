<script lang="ts" setup>
import type { BreadcrumbItem } from '@/types/blog.types';
import { Link } from '@inertiajs/vue3';
import clsx from 'clsx';
import { computed } from 'vue';

const props = defineProps<{
    breadcrumbs: BreadcrumbItem[];
}>();

const BREADCRUMB_CLASSES = {
    base: 'hover:underline',
    active: 'text-breadcrumb-link-active',
    inactive: 'text-breadcrumb-link',
} as const;

const breadcrumbCount = computed(() => props.breadcrumbs.length);

const isLastBreadcrumb = (index: number) => index === breadcrumbCount.value - 1;

const isBreadcrumbLink = (index: number, url?: string | null) => !isLastBreadcrumb(index) && !!url;

const getBreadcrumbClasses = (index: number) =>
    clsx(BREADCRUMB_CLASSES.base, isLastBreadcrumb(index) ? BREADCRUMB_CLASSES.active : BREADCRUMB_CLASSES.inactive);
</script>

<template>
    <ol v-if="breadcrumbs.length" aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1 text-xs md:text-sm">
        <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center font-semibold">
            <component
                :is="isBreadcrumbLink(index, crumb.url) ? Link : 'span'"
                :aria-current="isLastBreadcrumb(index) ? 'page' : undefined"
                :class="getBreadcrumbClasses(index)"
                :href="isBreadcrumbLink(index, crumb.url) ? crumb.url : undefined"
            >
                {{ crumb.label }}
            </component>

            <span v-if="!isLastBreadcrumb(index)" class="mx-2 text-breadcrumb-link opacity-60"> / </span>
        </li>
    </ol>
</template>
