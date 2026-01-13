<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import { TooltipButton } from '@/components/ui/tooltip';
import type { Category } from '@/types/blog.types';
import { Plus, X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    canCreate: boolean;
    categories: Category[];
    showCreate: boolean;
    form?: any; // external create form
}

interface Emits {
    (e: 'toggleCreate'): void;
    (e: 'submitCreate', form: any): void;
    (e: 'cancelCreate'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function handleToggleCreate() {
    emit('toggleCreate');
}

function handleSubmitCreate(form: any) {
    emit('submitCreate', form);
}

function handleCancelCreate() {
    emit('cancelCreate');
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ t('blogger.create_section.title') }}</h1>
            <TooltipButton
                :disabled="!props.canCreate"
                :variant="!props.canCreate ? 'muted' : showCreate ? 'exit' : 'constructive'"
                size="icon"
                tooltip-content=""
                @click="handleToggleCreate"
            >
                <X v-if="showCreate" />
                <Plus v-else />
                <template #tooltip>
                    <template v-if="!props.canCreate">
                        {{ t('blogger.create_section.quota_reached_tooltip') }}
                    </template>
                    <template v-else>
                        {{ showCreate ? t('blogger.create_section.close_button') : t('blogger.create_section.create_button') }}
                    </template>
                </template>
            </TooltipButton>
        </div>

        <!-- Create New Blog Form -->
        <BlogForm
            v-if="showCreate"
            :categories="categories"
            :form="props.form"
            :is-edit="false"
            id-prefix="new"
            @cancel="handleCancelCreate"
            @submit="handleSubmitCreate"
        />
    </div>
</template>
