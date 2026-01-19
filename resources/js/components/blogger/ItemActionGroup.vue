<script lang="ts" setup>
import { TooltipButton } from '@/components/ui/tooltip';
import { ChevronDown, ChevronUp, Pencil, Plus, X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    isEditing: boolean;
    isCreatingPost: boolean;
    isPostsExpanded: boolean;
    showAddPost?: boolean;
}

withDefaults(defineProps<Props>(), {
    showAddPost: true,
});

defineEmits<{
    (e: 'edit'): void;
    (e: 'createPost'): void;
    (e: 'togglePosts'): void;
}>();
</script>

<template>
    <div class="flex items-center gap-2">
        <slot name="prefix" />

        <TooltipButton
            :tooltip-content="isEditing ? t('blogger.actions.close') : t('blogger.actions.edit')"
            size="icon"
            variant="toggle"
            @click="$emit('edit')"
        >
            <X v-if="isEditing" />
            <Pencil v-else />
        </TooltipButton>

        <TooltipButton
            :tooltip-content="isPostsExpanded ? t('blogger.actions.hide_posts') : t('blogger.actions.show_posts')"
            size="icon"
            variant="toggle"
            @click="$emit('togglePosts')"
        >
            <ChevronUp v-if="isPostsExpanded" />
            <ChevronDown v-else />
        </TooltipButton>

        <TooltipButton
            v-if="showAddPost"
            :tooltip-content="isCreatingPost ? t('blogger.actions.close') : t('blogger.actions.add_post')"
            :variant="isCreatingPost ? 'exit' : 'constructive'"
            size="icon"
            @click="$emit('createPost')"
        >
            <X v-if="isCreatingPost" />
            <Plus v-else />
        </TooltipButton>

        <slot name="suffix" />
    </div>
</template>
