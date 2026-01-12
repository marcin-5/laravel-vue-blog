<script lang="ts" setup>
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import type { PostExtension } from '@/types/blog.types';
import { ref } from 'vue';

defineProps<{
    extensions: PostExtension[];
}>();

const expandedIds = ref<Set<number>>(new Set());
const lockedIds = ref<Set<number>>(new Set());

function toggleExtension(id: number) {
    const newExpanded = new Set(expandedIds.value);
    if (newExpanded.has(id)) {
        // Don't collapse if locked
        if (lockedIds.value.has(id)) return;
        newExpanded.delete(id);
    } else {
        // Collapse others that are NOT locked
        newExpanded.forEach((expandedId) => {
            if (!lockedIds.value.has(expandedId)) {
                newExpanded.delete(expandedId);
            }
        });
        newExpanded.add(id);
    }
    expandedIds.value = newExpanded;
}

function toggleLock(id: number, checked: boolean) {
    const newLocked = new Set(lockedIds.value);
    if (checked) {
        newLocked.add(id);
    } else {
        newLocked.delete(id);
    }
    lockedIds.value = newLocked;
}
</script>

<template>
    <div v-if="extensions && extensions.length > 0" class="mt-8 space-y-4">
        <div v-for="extension in extensions" :key="extension.id" class="rounded-lg border border-border bg-card">
            <Collapsible :open="expandedIds.has(extension.id)">
                <div class="flex items-center justify-between p-4">
                    <CollapsibleTrigger as-child>
                        <button
                            class="flex flex-1 items-center justify-between text-left font-semibold transition-colors hover:text-primary"
                            @click="toggleExtension(extension.id)"
                        >
                            <span>{{ extension.title }}</span>
                            <svg
                                :class="{ 'rotate-180': expandedIds.has(extension.id) }"
                                class="h-5 w-5 transition-transform duration-200"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                            </svg>
                        </button>
                    </CollapsibleTrigger>
                    <div v-if="extensions.length > 1" class="ml-4 flex items-center gap-2">
                        <Checkbox :checked="lockedIds.has(extension.id)" @update:checked="(val: boolean) => toggleLock(extension.id, val)" />
                        <span class="text-xs text-muted-foreground">{{ $t('blog.post.lock_extension') }}</span>
                    </div>
                </div>
                <CollapsibleContent>
                    <div class="border-t border-border p-4">
                        <article
                            :style="{ fontFamily: 'var(--blog-body-font)' }"
                            class="prose dark:prose-invert max-w-none"
                            v-html="extension.contentHtml"
                        />
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </div>
</template>
