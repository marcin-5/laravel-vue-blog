<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Category {
    id: number;
    name: string;
    display_order: number;
}

const props = defineProps<{
    blogId: number;
    category: Category | null;
}>();

const emit = defineEmits<{
    saved: [];
    cancel: [];
}>();

const { t } = useI18n();
const form = useForm({ name: '' });

watch(
    () => props.category,
    (category) => {
        form.clearErrors();
        form.name = category?.name ?? '';
    },
    { immediate: true },
);

function submit() {
    if (props.category) {
        form.patch(route('blogger.recommended-link-categories.update', [props.blogId, props.category.id]), {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });

        return;
    }

    form.post(route('blogger.recommended-link-categories.store', props.blogId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('saved');
        },
    });
}

function cancel() {
    form.reset();
    form.clearErrors();
    emit('cancel');
}
</script>

<template>
    <section class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
        <div class="mb-4 flex items-center gap-3">
            <span class="grid size-9 place-items-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">{{ category ? '↗' : '+' }}</span>
            <h2 class="text-lg font-semibold">{{ category ? t('blogger.recommended_link_categories.edit_heading') : t('blogger.recommended_link_categories.create_heading') }}</h2>
        </div>

        <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="submit">
            <div class="min-w-0 flex-1 space-y-1.5">
                <label class="text-sm font-medium" for="recommended-link-category-name">{{ t('blogger.recommended_link_categories.name') }}</label>
                <input
                    id="recommended-link-category-name"
                    v-model="form.name"
                    autocomplete="off"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring"
                    maxlength="255"
                    required
                    type="text"
                />
                <InputError :message="form.errors.name" />
            </div>
            <div class="flex gap-2">
                <Button :disabled="form.processing" type="submit" variant="constructive">
                    {{ category ? t('blogger.recommended_link_categories.save') : t('blogger.recommended_link_categories.create') }}
                </Button>
                <Button v-if="category" type="button" variant="ghost" @click="cancel">{{ t('blogger.recommended_link_categories.cancel') }}</Button>
            </div>
        </form>
    </section>
</template>