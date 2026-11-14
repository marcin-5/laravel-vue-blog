<script lang="ts" setup>
import EntityThemeSection from '@/components/blogger/EntityThemeSection.vue';
import FormSelectField from '@/components/blogger/FormSelectField.vue';
import { Button } from '@/components/ui/button';
import { useBloggerFormTranslations } from '@/composables/useBloggerFormTranslations';
import type { BlogTheme, ThemeManagementEntity } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    entityType: 'blog' | 'group';
    entities: ThemeManagementEntity[];
}

const props = defineProps<Props>();
const { t } = useI18n();
const { actionTranslations, themeSectionTranslations } = useBloggerFormTranslations();

const selectedEntityId = ref(String(props.entities[0]?.id ?? ''));
const selectedEntity = computed(() => props.entities.find((entity) => String(entity.id) === selectedEntityId.value));
const entityOptions = computed(() => props.entities.map((entity) => ({ value: entity.id, label: entity.name })));
const form = useForm<{ theme: BlogTheme }>({ theme: selectedEntity.value?.theme ?? {} });
const title = computed(() => t(props.entityType === 'blog' ? 'blogger.theme_management.blog_title' : 'blogger.theme_management.group_title'));
const selectLabel = computed(() => t(props.entityType === 'blog' ? 'blogger.theme_management.select_blog' : 'blogger.theme_management.select_group'));
const emptyMessage = computed(() => t(props.entityType === 'blog' ? 'blogger.theme_management.no_blogs' : 'blogger.theme_management.no_groups'));

watch(selectedEntity, (entity) => {
    form.theme = entity?.theme ?? {};
    form.clearErrors();
});

function saveTheme(): void {
    if (!selectedEntity.value) {
        return;
    }

    const updateRoute = props.entityType === 'blog' ? 'blogs.update' : 'blogger.groups.content.update';

    form.patch(route(updateRoute, selectedEntity.value.id), { preserveScroll: true });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <h1 class="text-xl font-semibold">{{ title }}</h1>

        <form v-if="selectedEntity" class="space-y-4" @submit.prevent="saveTheme">
            <FormSelectField
                id="theme-management-entity"
                v-model="selectedEntityId"
                :label="selectLabel"
                :options="entityOptions"
            />

            <EntityThemeSection
                v-model="form.theme"
                :errors="form.errors"
                :id-prefix="`theme-management-${props.entityType}-${selectedEntity.id}`"
                :translations="themeSectionTranslations"
            />

            <div class="flex justify-end">
                <Button :disabled="form.processing" type="submit" variant="constructive">
                    {{ form.processing ? actionTranslations.saving : actionTranslations.save }}
                </Button>
            </div>
        </form>

        <p v-else class="text-sm text-muted-foreground">{{ emptyMessage }}</p>
    </div>
</template>