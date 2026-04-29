<script lang="ts" setup>
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Info } from 'lucide-vue-next';

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
        <ul class="space-y-2">
            <li v-for="link in items" :key="link.id || link.url" class="rounded-md border border-border bg-card p-2">
                <a :href="link.url" class="font-medium text-primary hover:text-primary-foreground" rel="noopener noreferrer" target="_blank">
                    {{ link.title }}
                </a>
                <div v-if="link.reason" class="mt-1 flex items-center gap-1.5">
                    <p class="text-xs text-muted-foreground">{{ link.reason }}</p>

                    <Popover v-if="link.description">
                        <PopoverTrigger as-child>
                            <button
                                :aria-label="$t('actions.info') || 'Info'"
                                class="inline-flex text-muted-foreground/60 transition-colors hover:text-primary focus:outline-none"
                                type="button"
                            >
                                <Info class="size-3.5" />
                            </button>
                        </PopoverTrigger>
                        <PopoverContent class="text-sm">
                            {{ link.description }}
                        </PopoverContent>
                    </Popover>
                </div>
            </li>
        </ul>
    </section>
    <div v-else />
</template>
