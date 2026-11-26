<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface RecommendedLink {
    id: number;
    url: string;
    name: string | null;
    description: string | null;
}

const props = defineProps<{
    blog: { id: number; name: string };
    recommendedLinks: RecommendedLink[];
}>();

const { t } = useI18n();
const editingId = ref<number | null>(null);
const title = computed(() => t('blogger.recommended_links.title', { blog: props.blog.name }));
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.index'), href: '/blogs' },
    { title: title.value, href: route('blogger.recommended-links.index', props.blog.id) },
]);

const createForm = useForm({ url: '', name: '', description: '' });
const editForm = useForm({ url: '', name: '', description: '' });

function submitCreate() {
    createForm.post(route('blogger.recommended-links.store', props.blog.id), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function startEdit(link: RecommendedLink) {
    editingId.value = link.id;
    editForm.clearErrors();
    editForm.url = link.url;
    editForm.name = link.name ?? '';
    editForm.description = link.description ?? '';
}

function cancelEdit() {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function submitEdit(linkId: number) {
    editForm.patch(route('blogger.recommended-links.update', [props.blog.id, linkId]), {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
}

function deleteLink(linkId: number) {
    if (confirm(t('blogger.recommended_links.confirm_delete'))) {
        router.delete(route('blogger.recommended-links.destroy', [props.blog.id, linkId]), { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="title" />
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
            <h1 class="text-xl font-semibold">{{ title }}</h1>

            <form class="space-y-4 rounded-md border p-4" @submit.prevent="submitCreate">
                <div>
                    <label class="mb-1 block text-sm font-medium" for="recommended-link-url">{{ t('blogger.recommended_links.url') }}</label>
                    <input id="recommended-link-url" v-model="createForm.url" class="w-full rounded-md border px-3 py-2" required type="url" />
                    <InputError :message="createForm.errors.url" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="recommended-link-name">{{ t('blogger.recommended_links.name') }}</label>
                    <input id="recommended-link-name" v-model="createForm.name" class="w-full rounded-md border px-3 py-2" type="text" />
                    <InputError :message="createForm.errors.name" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="recommended-link-description">{{
                        t('blogger.recommended_links.description')
                    }}</label>
                    <textarea
                        id="recommended-link-description"
                        v-model="createForm.description"
                        class="w-full rounded-md border px-3 py-2"
                        rows="3"
                    />
                    <InputError :message="createForm.errors.description" />
                </div>
                <Button :disabled="createForm.processing" type="submit" variant="constructive">
                    {{ t('blogger.recommended_links.add') }}
                </Button>
            </form>

            <p v-if="recommendedLinks.length === 0" class="text-sm text-muted-foreground">
                {{ t('blogger.recommended_links.empty') }}
            </p>

            <ul v-else class="space-y-4">
                <li v-for="link in recommendedLinks" :key="link.id" class="rounded-md border p-4">
                    <form v-if="editingId === link.id" class="space-y-4" @submit.prevent="submitEdit(link.id)">
                        <div>
                            <label :for="`edit-url-${link.id}`" class="mb-1 block text-sm font-medium">{{
                                t('blogger.recommended_links.url')
                            }}</label>
                            <input
                                :id="`edit-url-${link.id}`"
                                v-model="editForm.url"
                                class="w-full rounded-md border px-3 py-2"
                                required
                                type="url"
                            />
                            <InputError :message="editForm.errors.url" />
                        </div>
                        <div>
                            <label :for="`edit-name-${link.id}`" class="mb-1 block text-sm font-medium">{{
                                t('blogger.recommended_links.name')
                            }}</label>
                            <input :id="`edit-name-${link.id}`" v-model="editForm.name" class="w-full rounded-md border px-3 py-2" type="text" />
                            <InputError :message="editForm.errors.name" />
                        </div>
                        <div>
                            <label :for="`edit-description-${link.id}`" class="mb-1 block text-sm font-medium">{{
                                t('blogger.recommended_links.description')
                            }}</label>
                            <textarea
                                :id="`edit-description-${link.id}`"
                                v-model="editForm.description"
                                class="w-full rounded-md border px-3 py-2"
                                rows="3"
                            />
                            <InputError :message="editForm.errors.description" />
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
                        <div class="flex gap-2">
                            <Button size="sm" type="button" variant="toggle" @click="startEdit(link)">{{
                                t('blogger.recommended_links.edit')
                            }}</Button>
                            <Button size="sm" type="button" variant="destructive" @click="deleteLink(link.id)">{{
                                t('blogger.recommended_links.delete')
                            }}</Button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
