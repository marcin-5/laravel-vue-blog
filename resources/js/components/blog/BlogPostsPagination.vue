<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

type PaginationLink = {
    active: boolean;
    url: string | null;
    label: string | number;
};

defineProps<{
    links: PaginationLink[];
}>();

const { t } = useI18n();

const PAGINATION_LABEL_TRANSLATIONS: Record<string, string> = {
    previous: 'blog.pagination.previous',
    next: 'blog.pagination.next',
};

const PAGINATION_DECORATION_PATTERN = /[«»]|&[lr]aquo;/g;

const navigationStyle = {
    fontFamily: 'var(--blog-nav-font)',
};

const paginationBaseClasses = 'rounded border px-2 py-1 text-sm text-primary transition-colors';
const activePaginationClasses = 'border-foreground bg-background';
const inactivePaginationClasses = 'border-border bg-card';
const enabledPaginationClasses = 'hover:bg-secondary';
const disabledPaginationClasses = 'pointer-events-none opacity-50';

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
    <nav :aria-label="t('blog.pagination.aria')" :style="navigationStyle" class="mt-4 flex items-center justify-center gap-1">
        <Link v-for="(link, index) in links" :key="index" :class="getPaginationLinkClasses(link)" :href="link.url || ''" preserve-scroll>
            {{ translatePaginationLabel(String(link.label)) }}
        </Link>
    </nav>
</template>
