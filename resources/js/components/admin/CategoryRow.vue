<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { i18n } from '@/i18n';
import type { CategoryRow } from '@/types/admin.types';
import { localizedName } from '@/utils/localization';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    category: CategoryRow;
    supportedLocales: readonly string[];
}>();

const isEditing = ref(false);

const editForm = useForm({
    name: '' as string,
    locale: ((i18n.global.locale.value as string) || 'en') as string,
});

function getCurrentUiLocale(): string {
    return (i18n.global.locale.value as string) || 'en';
}

function resolveInitialLocale(): string {
    const uiLocale = getCurrentUiLocale();

    if (typeof props.category.name === 'string') {
        return uiLocale;
    }

    const availableLocales = Object.keys(props.category.name || {});
    if (availableLocales.includes(uiLocale) || availableLocales.length === 0) {
        return uiLocale;
    }

    return availableLocales[0];
}

function resolveCategoryName(locale: string): string {
    if (typeof props.category.name === 'string') {
        return props.category.name;
    }

    return (props.category.name?.[locale] ?? props.category.name?.en ?? Object.values(props.category.name ?? {})[0] ?? '') as string;
}

function startEdit() {
    isEditing.value = true;
    editForm.reset();

    const initialLocale = resolveInitialLocale();
    editForm.locale = initialLocale;
    editForm.name = resolveCategoryName(initialLocale);
}

function cancelEdit() {
    isEditing.value = false;
    editForm.reset();
}

function submitEdit() {
    editForm.patch(route('admin.categories.update', props.category.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isEditing.value = false;
        },
    });
}

function destroyCategory() {
    const categoryName = localizedName(props.category.name);
    const confirmationMessage =
        t('admin.categories.delete_confirm', { name: categoryName }) || `Delete category "${categoryName}"? This will remove it from all blogs.`;

    if (!confirm(confirmationMessage)) {
        return;
    }

    router.delete(route('admin.categories.destroy', props.category.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

watch(
    () => editForm.locale,
    (newLocale) => {
        if (!isEditing.value) {
            return;
        }

        editForm.name = resolveCategoryName(newLocale);
    },
);
</script>

<template>
    <tr class="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border">
        <td class="py-2 pr-4">
            <div v-if="!isEditing">{{ localizedName(category.name) }}</div>
            <div v-else class="flex items-end gap-2">
                <div>
                    <label :for="`edit-locale-${category.id}`" class="sr-only">{{ t('admin.categories.language_label') }}</label>
                    <Select v-model="editForm.locale">
                        <SelectTrigger :id="`edit-locale-${category.id}`" class="h-8 w-[80px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="loc in supportedLocales" :key="`edit-${category.id}-${loc}`" :value="loc">
                                {{ loc.toUpperCase() }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="editForm.errors.locale" />
                </div>
                <div class="flex-1">
                    <label :for="`edit-name-${category.id}`" class="sr-only">{{ t('admin.categories.name_label') }}</label>
                    <input :id="`edit-name-${category.id}`" v-model="editForm.name" class="w-full rounded-md border px-2 py-1" required type="text" />
                    <InputError :message="editForm.errors.name" />
                </div>
            </div>
        </td>
        <td class="py-2 pr-4">
            <code class="text-xs">{{ category.slug }}</code>
        </td>
        <td class="py-2 pr-4">{{ category.blogs_count ?? 0 }}</td>
        <td class="py-2 pr-4">
            <div v-if="!isEditing" class="flex gap-2">
                <Button size="sm" type="button" variant="toggle" @click="startEdit">{{
                    t('admin.users.actions.save') === 'Save' ? 'Edit' : 'Edytuj'
                }}</Button>
                <Button size="sm" type="button" variant="destructive" @click="destroyCategory">{{ t('common.delete') }}</Button>
            </div>
            <div v-else class="flex gap-2">
                <Button :disabled="editForm.processing" size="sm" type="button" variant="constructive" @click="submitEdit">
                    {{ editForm.processing ? t('admin.categories.saving_button') : t('admin.users.actions.save') }}
                </Button>
                <Button size="sm" type="button" variant="destructive" @click="cancelEdit">{{ t('common.cancel') }}</Button>
            </div>
        </td>
    </tr>
</template>
