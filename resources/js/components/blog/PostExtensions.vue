<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { PostExtension } from '@/types/blog.types';
import { Lock, LockOpen } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    extensions: PostExtension[];
    theme?: Record<string, string>;
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

function toggleLock(id: number) {
    const newLocked = new Set(lockedIds.value);
    if (newLocked.has(id)) {
        newLocked.delete(id);
    } else {
        newLocked.add(id);
    }
    lockedIds.value = newLocked;
}
</script>

<template>
    <div v-if="extensions && extensions.length > 0" class="mt-8 space-y-4 text-foreground">
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
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        :variant="lockedIds.has(extension.id) ? 'toggle' : 'locked'"
                                        class="h-8 w-8"
                                        size="icon"
                                        @click="toggleLock(extension.id)"
                                    >
                                        <Lock v-if="lockedIds.has(extension.id)" class="h-4 w-4" />
                                        <LockOpen v-else class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent :style="theme" class="mb-1 bg-primary-foreground px-4 py-2 text-secondary-foreground">
                                    {{ lockedIds.has(extension.id) ? $t('blog.post.unlock_extension') : $t('blog.post.lock_extension') }}
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
                <CollapsibleContent>
                    <div class="border-t border-border p-4">
                        <article
                            :style="{ fontFamily: 'var(--blog-body-font)', fontSize: 'calc(1rem * var(--blog-body-scale))' }"
                            class="prose dark:prose-invert max-w-none text-primary"
                            v-html="extension.contentHtml"
                        />
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </div>
</template>
