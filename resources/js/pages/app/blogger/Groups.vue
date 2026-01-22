<script lang="ts" setup>
import CreateEntitySection from '@/components/blogger/CreateEntitySection.vue';
import GroupForm from '@/components/blogger/GroupForm.vue';
import GroupListItem from '@/components/blogger/GroupListItem.vue';
import { Button } from '@/components/ui/button';
import { useGroupForm } from '@/composables/useGroupForm';
import { usePostForm } from '@/composables/usePostForm';
import { useUIState } from '@/composables/useUIState';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AdminGroup as Group } from '@/types/blog.types';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ groups: Group[]; canCreate: boolean }>();

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.groups'), href: '/groups' },
];

function handleReload() {
    router.reload({ only: ['groups'] });
}

const { showCreate, editingId, createForm, editForm, openCreateForm, closeCreateForm, submitCreate, startEdit, cancelEdit, submitEdit } =
    useGroupForm();

const {
    creatingPostForId,
    editingPostId,
    postForm,
    postEditForm,
    startCreatePost,
    cancelCreatePost,
    submitCreatePost,
    startEditPost,
    cancelEditPost,
    submitEditPost,
} = usePostForm();

const { expandedPostsForId, expandedExtensionsForId, togglePosts, toggleExtensions } = useUIState();

function handleStartEdit(group: Group) {
    creatingPostForId.value = null;
    expandedPostsForId.value = null;
    expandedExtensionsForId.value = null;
    editingPostId.value = null;
    postForm.reset();
    postEditForm.reset();

    startEdit(group);
}

function handleStartCreatePost(group: Group) {
    editingId.value = null;
    expandedPostsForId.value = null;
    expandedExtensionsForId.value = null;
    editingPostId.value = null;
    editForm.reset();
    postEditForm.reset();

    startCreatePost(group as any); // Adapt to the expectation of the composable
}

function handleTogglePosts(group: Group) {
    editingId.value = null;
    creatingPostForId.value = null;
    editingPostId.value = null;
    expandedExtensionsForId.value = null;
    editForm.reset();
    postForm.reset();
    postEditForm.reset();

    togglePosts(group as any);
}

function handleStartEditPost(post: any) {
    editingId.value = null;
    creatingPostForId.value = null;
    expandedExtensionsForId.value = null;
    editForm.reset();
    postForm.reset();

    startEditPost(post);
}

function handleToggleExtensions(post: any) {
    editingId.value = null;
    creatingPostForId.value = null;
    editingPostId.value = null;
    editForm.reset();
    postForm.reset();
    postEditForm.reset();

    toggleExtensions(post);
}

function handleToggleCreate() {
    if (showCreate.value) {
        closeCreateForm();
    } else {
        openCreateForm();
    }
}
</script>

<template>
    <Head :title="$t('blogger.groups.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <CreateEntitySection
                :can-create="props.canCreate"
                :form="createForm"
                :show-create="showCreate"
                :title="t('blogger.groups.create_section_title')"
                :tooltip-create="t('blogger.groups.create_group_tooltip')"
                :tooltip-limit="t('blogger.groups.limit_reached_tooltip')"
                @cancel="closeCreateForm"
                @submit="() => submitCreate()"
                @toggle="handleToggleCreate"
            >
                <template #form="{ form, onCancel, onSubmit }">
                    <GroupForm :form="form" :is-edit="false" id-prefix="new" @cancel="onCancel" @submit="onSubmit" />
                </template>
            </CreateEntitySection>

            <!-- Groups List -->
            <div class="space-y-3">
                <GroupListItem
                    v-for="group in props.groups"
                    :key="group.id"
                    :edit-form="editForm"
                    :editing-post-id="editingPostId"
                    :expanded-extensions-for-id="expandedExtensionsForId"
                    :is-creating-post="creatingPostForId === group.id"
                    :is-editing="editingId === group.id"
                    :is-posts-expanded="expandedPostsForId === group.id"
                    :item="group"
                    :post-edit-form="postEditForm"
                    :post-form="postForm"
                    @edit="handleStartEdit"
                    @cancel-edit="cancelEdit"
                    @cancel-create-post="cancelCreatePost"
                    @cancel-edit-post="cancelEditPost"
                    @create-post="handleStartCreatePost"
                    @edit-post="handleStartEditPost"
                    @post-updated="handleReload"
                    @submit-create-post="() => submitCreatePost()"
                    @submit-edit="() => submitEdit(group)"
                    @submit-edit-post="() => submitEditPost()"
                    @toggle-extensions="handleToggleExtensions"
                    @toggle-posts="handleTogglePosts"
                />

                <div v-if="props.groups.length === 0" class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
                    {{ $t('blogger.groups.empty') }}
                    <span :title="!props.canCreate ? $t('blogger.groups.limit_reached_hint') : ''">
                        <Button
                            :disabled="!props.canCreate"
                            :variant="!props.canCreate ? 'muted' : 'link'"
                            class="ml-2"
                            type="button"
                            @click="openCreateForm"
                        >
                            {{ $t('blogger.groups.empty_cta') }}
                        </Button>
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
