<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { router, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Pencil, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Category {
    id: number;
    name: string;
    display_order: number;
}

interface RecommendedLink {
    id: number;
    url: string;
    name: string | null;
    description: string | null;
    category_id: number | null;
    display_order: number;
}

interface LinkSection {
    categoryId: number | null;
    name: string;
    links: RecommendedLink[];
}

const props = defineProps<{
    blogId: number;
    categories: Category[];
    recommendedLinks: RecommendedLink[];
}>();

const { t } = useI18n();
const { toast } = useToast();
const links = ref([...props.recommendedLinks]);
const editingId = ref<number | null>(null);
const isReordering = ref(false);
const editForm = useForm({
    url: '',
    name: '',
    description: '',
    category_id: null as number | null,
});
const sections = computed<LinkSection[]>(() => {
    const orderedLinks = [...links.value].sort((a, b) => a.display_order - b.display_order || a.id - b.id);

    return [
        {
            categoryId: null,
            name: t('blogger.recommended_links.uncategorized'),
            links: orderedLinks.filter((link) => link.category_id === null),
        },
        ...props.categories.map((category) => ({
            categoryId: category.id,
            name: category.name,
            links: orderedLinks.filter((link) => link.category_id === category.id),
        })),
    ];
});

watch(
    () => props.recommendedLinks,
    (updatedLinks) => {
        links.value = [...updatedLinks];
    },
);

function startEdit(link: RecommendedLink) {
    editingId.value = link.id;
    editForm.clearErrors();
    editForm.url = link.url;
    editForm.name = link.name ?? '';
    editForm.description = link.description ?? '';
    editForm.category_id = link.category_id;
}

function cancelEdit() {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function submitEdit(linkId: number) {
    editForm.patch(route('blogger.recommended-links.update', [props.blogId, linkId]), {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
}

function deleteLink(link: RecommendedLink) {
    if (!confirm(t('blogger.recommended_links.confirm_delete'))) {
        return;
    }

    router.delete(route('blogger.recommended-links.destroy', [props.blogId, link.id]), { preserveScroll: true });
}

function moveLink(section: LinkSection, index: number, direction: -1 | 1) {
    const destination = index + direction;
    if (destination < 0 || destination >= section.links.length || isReordering.value) {
        return;
    }

    const previousLinks = [...links.value];
    const orderedSectionLinks = [...section.links];
    const [link] = orderedSectionLinks.splice(index, 1);
    orderedSectionLinks.splice(destination, 0, link);
    const reorderedIds = orderedSectionLinks.map((item) => item.id);
    const reorderedOrder = new Map(reorderedIds.map((id, displayOrder) => [id, displayOrder]));

    links.value = links.value.map((item) =>
        reorderedOrder.has(item.id) ? { ...item, display_order: reorderedOrder.get(item.id)! } : item,
    );
    isReordering.value = true;

    router.put(
        route('blogger.recommended-links.reorder', props.blogId),
        { category_id: section.categoryId, link_ids: reorderedIds },
        {
            only: ['recommendedLinks'],
            preserveScroll: true,
            onError: () => {
                links.value = previousLinks;
                toast({
                    title: t('blogger.recommended_links.reorder_failed'),
                    variant: 'destructive',
                });
            },
            onFinish: () => {
                isReordering.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="space-y-5">
        <section
            v-for="section in sections"
            :key="section.categoryId ?? 'uncategorized'"
            class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm"
        >
            <header class="flex items-center justify-between gap-4 border-b border-sidebar-border/70 px-4 py-3 sm:px-5">
                <h2 class="font-semibold">{{ section.name }}</h2>
                <span class="rounded-full bg-muted px-2.5 py-1 text-xs tabular-nums text-muted-foreground">{{ section.links.length }}</span>
            </header>

            <ol v-if="section.links.length" class="divide-y divide-sidebar-border/60">
                <li v-for="(link, index) in section.links" :key="link.id" class="px-4 py-4 sm:px-5">
                    <form v-if="editingId === link.id" class="space-y-4" @submit.prevent="submitEdit(link.id)">
                        <div>
                            <label :for="`edit-url-${link.id}`" class="mb-1 block text-sm font-medium">{{ t('blogger.recommended_links.url') }}</label>
                            <input
                                :id="`edit-url-${link.id}`"
                                v-model="editForm.url"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                                type="url"
                            />
                            <InputError :message="editForm.errors.url" />
                        </div>
                        <div>
                            <label :for="`edit-name-${link.id}`" class="mb-1 block text-sm font-medium">{{ t('blogger.recommended_links.name') }}</label>
                            <input
                                :id="`edit-name-${link.id}`"
                                v-model="editForm.name"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                type="text"
                            />
                            <InputError :message="editForm.errors.name" />
                        </div>
                        <div>
                            <label :for="`edit-description-${link.id}`" class="mb-1 block text-sm font-medium">{{ t('blogger.recommended_links.description') }}</label>
                            <textarea
                                :id="`edit-description-${link.id}`"
                                v-model="editForm.description"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                rows="3"
                            />
                            <InputError :message="editForm.errors.description" />
                        </div>
                        <div>
                            <label :for="`edit-category-${link.id}`" class="mb-1 block text-sm font-medium">{{ t('blogger.recommended_links.category') }}</label>
                            <select
                                :id="`edit-category-${link.id}`"
                                v-model="editForm.category_id"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option :value="null">{{ t('blogger.recommended_links.uncategorized') }}</option>
                                <option v-for="category in props.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                            <InputError :message="editForm.errors.category_id" />
                        </div>
                        <div class="flex gap-2">
                            <Button :disabled="editForm.processing" type="submit">{{ t('blogger.recommended_links.save') }}</Button>
                            <Button type="button" variant="ghost" @click="cancelEdit">{{ t('blogger.recommended_links.cancel') }}</Button>
                        </div>
                    </form>

                    <div v-else class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <a class="font-medium break-all underline" :href="link.url" rel="noopener noreferrer" target="_blank">
                                {{ link.name || link.url }}
                            </a>
                            <p v-if="link.name" class="mt-1 text-sm break-all text-muted-foreground">{{ link.url }}</p>
                            <p v-if="link.description" class="mt-2 text-sm whitespace-pre-wrap">{{ link.description }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <Button
                                :aria-label="t('blogger.recommended_links.move_up')"
                                :disabled="isReordering || index === 0"
                                class="size-8"
                                size="icon"
                                type="button"
                                variant="ghost"
                                @click="moveLink(section, index, -1)"
                            >
                                <ArrowUp class="size-4" />
                            </Button>
                            <Button
                                :aria-label="t('blogger.recommended_links.move_down')"
                                :disabled="isReordering || index === section.links.length - 1"
                                class="size-8"
                                size="icon"
                                type="button"
                                variant="ghost"
                                @click="moveLink(section, index, 1)"
                            >
                                <ArrowDown class="size-4" />
                            </Button>
                            <Button
                                :aria-label="t('blogger.recommended_links.edit')"
                                class="size-8"
                                size="icon"
                                type="button"
                                variant="ghost"
                                @click="startEdit(link)"
                            >
                                <Pencil class="size-4" />
                            </Button>
                            <Button
                                :aria-label="t('blogger.recommended_links.delete')"
                                class="size-8 text-destructive hover:bg-destructive/10"
                                size="icon"
                                type="button"
                                variant="ghost"
                                @click="deleteLink(link)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </li>
            </ol>
            <p v-else class="px-5 py-6 text-sm text-muted-foreground">{{ t('blogger.recommended_links.empty_section') }}</p>
        </section>
    </div>
</template>