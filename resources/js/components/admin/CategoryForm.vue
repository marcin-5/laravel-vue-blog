<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { i18n } from '@/i18n';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    supportedLocales: readonly string[];
}>();

const createForm = useForm({
    name: '' as string,
    locale: ((i18n.global.locale.value as string) || 'en') as string,
});

function submitCreate() {
    createForm.post(route('admin.categories.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            const current = (i18n.global.locale.value as string) || 'en';
            createForm.reset();
            createForm.locale = current;
        },
    });
}
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <h2 class="mb-3 text-lg font-semibold">Create Category</h2>
        <form class="flex flex-wrap items-end gap-3" @submit.prevent="submitCreate">
            <div>
                <label class="mb-1 block text-sm font-medium" for="new-locale">Language</label>
                <select id="new-locale" v-model="createForm.locale" class="block rounded-md border px-3 py-2">
                    <option v-for="loc in props.supportedLocales" :key="`new-${loc}`" :value="loc">{{ loc.toUpperCase() }}</option>
                </select>
                <InputError :message="createForm.errors.locale" />
            </div>
            <div class="min-w-64">
                <label class="mb-1 block text-sm font-medium" for="new-name">Name</label>
                <input id="new-name" v-model="createForm.name" class="block w-full rounded-md border px-3 py-2" required type="text" />
                <InputError :message="createForm.errors.name" />
            </div>
            <div>
                <Button :disabled="createForm.processing" type="submit" variant="constructive">
                    {{ createForm.processing ? 'Creating…' : 'Create' }}
                </Button>
            </div>
        </form>
    </div>
</template>
