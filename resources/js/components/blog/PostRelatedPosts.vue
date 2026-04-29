<script lang="ts" setup>
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Link } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import { computed } from 'vue';

interface RelatedPostMinimal {
    id?: number;
    blog_id: number;
    related_post_id: number;
    reason?: string | null;
    excerpt?: string | null;
    display_order?: number;
    blog_slug?: string | null;
    related_post?: { id: number; title: string; slug: string } | null;
}

const props = defineProps<{
    title?: string;
    items?: RelatedPostMinimal[] | null;
}>();

const visibleItems = computed(() => (props.items ?? []).filter((i) => i.related_post && i.blog_slug));
</script>

<template>
    <section v-if="visibleItems.length" class="mt-10 space-y-2">
        <div class="text-xl font-semibold text-foreground">{{ $t('blog.post.related_posts') }}</div>
        <ul class="space-y-2">
            <li
                v-for="item in visibleItems"
                :key="`${item.blog_slug}-${item?.related_post?.slug}`"
                class="rounded-md border border-border bg-card p-2"
            >
                <Link
                    :href="route('blog.public.post', { blog: item.blog_slug, postSlug: item?.related_post?.slug })"
                    class="font-medium text-primary hover:text-primary-foreground"
                >
                    {{ item?.related_post?.title }}
                </Link>
                <div v-if="item.reason" class="mt-1 flex items-center gap-1.5">
                    <p class="text-sm text-muted-foreground">{{ item.reason }}</p>

                    <Popover v-if="item.excerpt">
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
                            {{ item.excerpt }}
                        </PopoverContent>
                    </Popover>
                </div>
            </li>
        </ul>
    </section>
    <div v-else />
</template>
