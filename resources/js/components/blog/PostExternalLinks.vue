<script lang="ts" setup>
interface ExternalLinkItem {
    id?: number;
    title: string;
    url: string;
    description?: string | null;
    reason?: string | null;
}

defineProps<{
    title?: string;
    items?: ExternalLinkItem[] | null;
}>();
</script>

<template>
    <section v-if="items && items.length" class="mt-10 space-y-2">
        <div class="text-xl font-semibold text-foreground">{{ $t('blog.post.external_links') }}</div>
        <ul class="space-y-1">
            <li v-for="link in items" :key="link.id || link.url" class="rounded-md border border-border bg-card p-2">
                <a :href="link.url" class="font-medium text-primary hover:text-primary-foreground" rel="noopener noreferrer" target="_blank">
                    {{ link.title }}
                </a>
                <p v-if="link.reason" class="mt-1 text-xs text-muted-foreground">{{ link.reason }}</p>
                <p v-if="link.description" class="mt-1 text-sm text-muted-foreground">{{ link.description }}</p>
            </li>
        </ul>
    </section>
    <div v-else />
</template>
