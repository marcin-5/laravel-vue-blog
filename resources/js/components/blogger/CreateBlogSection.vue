<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
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
            <Tooltip>
                <TooltipTrigger as-child>
                    <div>
                        <Button
                            :disabled="!props.canCreate"
                            :variant="!props.canCreate ? 'muted' : showCreate ? 'exit' : 'constructive'"
                            size="icon"
                            type="button"
                            @click="handleToggleCreate"
                        >
                            <X v-if="showCreate" />
                            <Plus v-else />
                        </Button>
                    </div>
                </TooltipTrigger>
                <TooltipContent>
                    <template v-if="!props.canCreate">
                        {{ t('blogger.create_section.quota_reached_tooltip') }}
                    </template>
                    <template v-else>
                        {{ showCreate ? t('blogger.create_section.close_button') : t('blogger.create_section.create_button') }}
                    </template>
                </TooltipContent>
            </Tooltip>
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
