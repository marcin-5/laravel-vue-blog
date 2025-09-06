<script lang="ts" setup>
import type { Category } from '@/types';
import { i18n } from '@/i18n';

interface Props {
    categories: Category[];
    selectedCategories: number[];
    idPrefix?: string;
}

interface Emits {
    (e: 'update:selectedCategories', value: number[]): void;
}

const props = withDefaults(defineProps<Props>(), {
    idPrefix: 'category',
});

const emit = defineEmits<Emits>();

function updateCategories(categoryIds: number[]) {
    emit('update:selectedCategories', categoryIds);
}

function localizedName(name: string | Record<string, string>): string {
    const locale = (i18n.global.locale.value as string) || 'en';
    if (typeof name === 'string') return name;
    return name?.[locale] ?? name?.en ?? Object.values(name ?? {})[0] ?? '';
}
</script>

<template>
    <div>
        <div class="mb-1 block text-sm font-medium">Categories</div>
        <div class="flex flex-wrap gap-3">
            <label v-for="category in props.categories" :key="`${props.idPrefix}-${category.id}`" class="inline-flex items-center gap-2">
                <input
                    :checked="props.selectedCategories.includes(category.id)"
                    :value="category.id"
                    type="checkbox"
                    @change="
                        (e) => {
                            const target = e.target as HTMLInputElement;
                            const categoryId = category.id;
                            const newSelection = target.checked
                                ? [...props.selectedCategories, categoryId]
                                : props.selectedCategories.filter((id) => id !== categoryId);
                            updateCategories(newSelection);
                        }
                    "
                />
                <span class="text-sm">{{ localizedName(category.name as any) }}</span>
            </label>
        </div>
    </div>
</template>
