<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import type { BlogVerificationRow } from '@/types/admin.types';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blog: BlogVerificationRow;
}>();

const { t } = useI18n();

const form = useForm({
    bing_site_verification_token: props.blog.bing_site_verification_token ?? '',
});

function saveToken(): void {
    form.patch(route('admin.blogs.bing-verification-token.update', props.blog.id), {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <tr class="border-b border-sidebar-border/70 last:border-b-0">
        <td class="py-2 pr-4">{{ props.blog.name }}</td>
        <td class="py-2 pr-4">{{ props.blog.owner_name }}</td>
        <td class="min-w-72 py-2 pr-4">
            <label class="sr-only" :for="`bing-token-${props.blog.id}`">{{ t('blogger.verification.token') }}</label>
            <input
                :id="`bing-token-${props.blog.id}`"
                v-model="form.bing_site_verification_token"
                class="w-full rounded-md border px-2 py-1"
                :maxlength="255"
                type="text"
            />
            <InputError :message="form.errors.bing_site_verification_token" />
        </td>
        <td class="py-2 pr-4">
            <Button :disabled="form.processing" size="sm" type="button" @click="saveToken">
                {{ form.processing ? t('blogger.verification.saving') : t('blogger.verification.save') }}
            </Button>
        </td>
    </tr>
</template>
