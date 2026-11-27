<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { ArrowDown, ArrowUp, Pencil, Trash2 } from '@lucide/vue';
import { useI18n } from 'vue-i18n';

interface Category {
    id: number;
    name: string;
    display_order: number;
}

const props = defineProps<{
    categories: Category[];
    processing: boolean;
}>();

const emit = defineEmits<{
    edit: [category: Category];
    delete: [category: Category];
    move: [index: number, direction: -1 | 1];
}>();

const { t } = useI18n();
</script>

<template>
    <section class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
        <div class="flex items-end justify-between gap-4 border-b border-sidebar-border/70 px-5 py-4">
            <div>
                <p class="text-xs font-semibold tracking-[0.16em] text-muted-foreground uppercase">{{ t('blogger.recommended_link_categories.order_label') }}</p>
                <h2 class="mt-1 text-lg font-semibold">{{ t('blogger.recommended_link_categories.list_heading') }}</h2>
            </div>
            <span class="rounded-full bg-muted px-2.5 py-1 text-xs tabular-nums text-muted-foreground">{{ props.categories.length }}</span>
        </div>

        <ol v-if="props.categories.length" class="divide-y divide-sidebar-border/60">
            <li v-for="(category, index) in props.categories" :key="category.id" class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/30 sm:px-5">
                <span class="w-7 shrink-0 text-right font-mono text-xs tabular-nums text-muted-foreground">{{ String(index + 1).padStart(2, '0') }}</span>
                <span class="min-w-0 flex-1 truncate font-medium">{{ category.name }}</span>
                <div class="flex items-center gap-1">
                    <Button
                        :aria-label="t('blogger.recommended_link_categories.move_up')"
                        :disabled="props.processing || index === 0"
                        class="size-8"
                        size="icon"
                        type="button"
                        variant="ghost"
                        @click="emit('move', index, -1)"
                    >
                        <ArrowUp class="size-4" />
                    </Button>
                    <Button
                        :aria-label="t('blogger.recommended_link_categories.move_down')"
                        :disabled="props.processing || index === props.categories.length - 1"
                        class="size-8"
                        size="icon"
                        type="button"
                        variant="ghost"
                        @click="emit('move', index, 1)"
                    >
                        <ArrowDown class="size-4" />
                    </Button>
                    <span class="mx-1 h-5 w-px bg-border" />
                    <Button
                        :aria-label="t('blogger.recommended_link_categories.edit')"
                        class="size-8"
                        size="icon"
                        type="button"
                        variant="ghost"
                        @click="emit('edit', category)"
                    >
                        <Pencil class="size-4" />
                    </Button>
                    <Button
                        :aria-label="t('blogger.recommended_link_categories.delete')"
                        class="size-8 text-destructive hover:bg-destructive/10"
                        size="icon"
                        type="button"
                        variant="ghost"
                        @click="emit('delete', category)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </li>
        </ol>
        <p v-else class="px-5 py-10 text-center text-sm text-muted-foreground">{{ t('blogger.recommended_link_categories.empty') }}</p>
    </section>
</template>