<script lang="ts" setup>
import TagForm from '@/components/blogger/TagForm.vue';
import TagLandingPageEditor from '@/components/blogger/TagLandingPageEditor.vue';
import TagList from '@/components/blogger/TagList.vue';
import { Button } from '@/components/ui/button';
import { useBloggerFormTranslations } from '@/composables/useBloggerFormTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, shallowRef } from 'vue';
import { useI18n } from 'vue-i18n';

interface Tag {
    id: number;
    name: string;
    slug: string;
    landing_page_content: string;
}

const props = defineProps<{
    blog: { id: number; name: string };
    tags: Tag[];
}>();

const { t } = useI18n();
const { createMarkdownTranslations } = useBloggerFormTranslations();
const markdownTranslations = createMarkdownTranslations();
const title = computed(() => t('blogger.tags.title', { blog: props.blog.name }));
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.index'), href: '/blogs' },
    { title: title.value, href: route('blogger.tags.index', props.blog.id) },
]);

const createForm = useForm({ name: '' });
const editForm = useForm({ name: '' });
const editingTagId = shallowRef<number | null>(null);
const selectedLandingPageTagId = shallowRef<number | null>(null);
const editingTag = computed(() => props.tags.find((tag) => tag.id === editingTagId.value) ?? null);
const selectedLandingPageTag = computed(() => props.tags.find((tag) => tag.id === selectedLandingPageTagId.value) ?? null);

function submitCreate() {
    createForm.post(route('blogger.tags.store', props.blog.id), {
        only: ['tags'],
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function startEditing(tag: Tag) {
    editingTagId.value = tag.id;
    editForm.name = tag.name;
    editForm.clearErrors();
}

function cancelEditing() {
    editingTagId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function submitUpdate() {
    if (!editingTag.value) {
        return;
    }

    editForm.patch(route('blogger.tags.update', [props.blog.id, editingTag.value.slug]), {
        only: ['tags'],
        preserveScroll: true,
        onSuccess: () => {
            editingTagId.value = null;
        },
    });
}

function deleteTag(tag: Tag) {
    router.delete(route('blogger.tags.destroy', [props.blog.id, tag.slug]), {
        only: ['tags'],
        preserveScroll: true,
        onBefore: () => window.confirm(t('blogger.tags.confirm_delete', { name: tag.name })),
        onSuccess: () => {
            if (editingTagId.value === tag.id) {
                cancelEditing();
            }
            if (selectedLandingPageTagId.value === tag.id) {
                selectedLandingPageTagId.value = null;
            }
        },
    });
}

function selectLandingPage(tag: Tag) {
    selectedLandingPageTagId.value = selectedLandingPageTagId.value === tag.id ? null : tag.id;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="title" />
        <main class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 sm:p-6">
            <header class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                <div class="space-y-2">
                    <p class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">{{ props.blog.name }}</p>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                    <p class="max-w-xl text-sm leading-relaxed text-muted-foreground">{{ t('blogger.tags.description') }}</p>
                </div>
                <Button as-child variant="outline">
                    <Link :href="route('blogs.index')">
                        <ArrowLeft class="mr-2 size-4" />
                        {{ t('blogger.tags.back_to_blogs') }}
                    </Link>
                </Button>
            </header>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                <h2 class="text-lg font-semibold">{{ t('blogger.tags.create_heading') }}</h2>
                <TagForm
                    id-prefix="tag-create"
                    :name="createForm.name"
                    :errors="createForm.errors"
                    :processing="createForm.processing"
                    :submit-label="t('blogger.tags.create_button')"
                    @update:name="createForm.name = $event"
                    @submit="submitCreate"
                />
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                <h2 class="text-lg font-semibold">{{ t('blogger.tags.section_title') }}</h2>
                <TagList
                    :tags="props.tags"
                    :editing-tag-id="editingTagId"
                    :editing-name="editForm.name"
                    :editing-errors="editForm.errors"
                    :editing-processing="editForm.processing"
                    :empty-label="t('blogger.tags.empty')"
                    :edit-label="t('blogger.tags.edit_button')"
                    :landing-page-label="t('blogger.tags.landing_page_button')"
                    :delete-label="t('blogger.tags.delete_button')"
                    :save-label="t('blogger.tags.save_button')"
                    :cancel-label="t('blogger.recommended_links.cancel')"
                    @edit="startEditing"
                    @delete="deleteTag"
                    @select-landing-page="selectLandingPage"
                    @update:name="editForm.name = $event"
                    @submit-update="submitUpdate"
                    @cancel-edit="cancelEditing"
                />
            </section>

            <section
                v-if="selectedLandingPageTag"
                :key="selectedLandingPageTag.id"
                class="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm"
            >
                <div>
                    <h2 class="text-lg font-semibold">{{ t('blogger.tags.landing_page_label') }}: {{ selectedLandingPageTag.name }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ t('blogger.tags.landing_page_description') }}</p>
                </div>
                <TagLandingPageEditor :blog-id="props.blog.id" :tag="selectedLandingPageTag" :translations="markdownTranslations" />
            </section>
        </main>
    </AppLayout>
</template>
