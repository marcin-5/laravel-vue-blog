<script lang="ts" setup>
import TagForm from '@/components/blogger/TagForm.vue';
import { Button } from '@/components/ui/button';

interface Tag {
    id: number;
    name: string;
    slug: string;
    landing_page_content: string;
}

const props = defineProps<{
    tags: Tag[];
    editingTagId: number | null;
    editingName: string;
    editingErrors: { name?: string; slug?: string };
    editingProcessing: boolean;
    emptyLabel: string;
    editLabel: string;
    landingPageLabel: string;
    deleteLabel: string;
    saveLabel: string;
    cancelLabel: string;
}>();

const emit = defineEmits<{
    edit: [tag: Tag];
    delete: [tag: Tag];
    selectLandingPage: [tag: Tag];
    'update:name': [value: string];
    submitUpdate: [];
    cancelEdit: [];
}>();
</script>

<template>
    <p v-if="props.tags.length === 0" class="text-sm text-muted-foreground">{{ props.emptyLabel }}</p>
    <ul v-else class="divide-y divide-sidebar-border/70">
        <li v-for="tag in props.tags" :key="tag.id" class="py-4 first:pt-0 last:pb-0">
            <TagForm
                v-if="props.editingTagId === tag.id"
                :id-prefix="`tag-edit-${tag.id}`"
                :name="props.editingName"
                :errors="props.editingErrors"
                :processing="props.editingProcessing"
                :submit-label="props.saveLabel"
                :cancel-label="props.cancelLabel"
                @update:name="emit('update:name', $event)"
                @submit="emit('submitUpdate')"
                @cancel="emit('cancelEdit')"
            />
            <div v-else class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-medium">{{ tag.name }}</h3>
                    <p class="text-sm text-muted-foreground">{{ tag.slug }}</p>
                </div>
                <div class="flex gap-2">
                    <Button size="sm" variant="outline" @click="emit('edit', tag)">{{ props.editLabel }}</Button>
                    <Button size="sm" variant="outline" @click="emit('selectLandingPage', tag)">
                        {{ props.landingPageLabel }}
                    </Button>
                    <Button size="sm" variant="destructive" @click="emit('delete', tag)">{{ props.deleteLabel }}</Button>
                </div>
            </div>
        </li>
    </ul>
</template>
