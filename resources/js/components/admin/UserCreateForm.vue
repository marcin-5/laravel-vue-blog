<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { type Role, useUserPermissions } from '@/composables/useUserPermissions';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    currentUserIsAdmin?: boolean;
    roles: Role[];
}>();

const createForm = useForm({
    name: '' as string,
    email: '' as string,
    password: '' as string,
    role: 'user' as Role,
    blog_quota: 0 as number | null,
});

const { canEditQuotaByRole } = useUserPermissions({
    currentUserIsAdmin: props.currentUserIsAdmin,
});

const canEditNewQuota = computed(() => canEditQuotaByRole(createForm.role));

function submitCreate() {
    const payload: Record<string, unknown> = {
        name: createForm.name,
        email: createForm.email,
        password: createForm.password,
        role: createForm.role,
    };

    if (canEditNewQuota.value) {
        payload.blog_quota = createForm.blog_quota ?? (createForm.role === 'blogger' ? 1 : 0);
    }

    createForm.post(route('admin.users.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            createForm.reset();
            createForm.role = 'user';
            createForm.blog_quota = 0;
        },
    });
}
</script>

<template>
    <div class="mb-6 rounded-md border border-dashed p-4">
        <div class="mb-2 text-sm font-medium">{{ $t('admin.users.create.title') }}</div>
        <form class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent="submitCreate">
            <div>
                <input
                    id="new-name"
                    v-model="createForm.name"
                    :placeholder="$t('admin.users.create.name')"
                    class="w-full rounded-md border bg-background px-2 py-1 text-foreground"
                    required
                    type="text"
                />
                <InputError :message="createForm.errors.name" />
            </div>
            <div>
                <input
                    id="new-email"
                    v-model="createForm.email"
                    :placeholder="$t('admin.users.create.email')"
                    class="w-full rounded-md border bg-background px-2 py-1 text-foreground"
                    required
                    type="email"
                />
                <InputError :message="createForm.errors.email" />
            </div>
            <div>
                <input
                    id="new-password"
                    v-model="createForm.password"
                    :placeholder="$t('admin.users.create.password')"
                    class="w-full rounded-md border bg-background px-2 py-1 text-foreground"
                    required
                    type="password"
                />
                <InputError :message="createForm.errors.password" />
            </div>
            <div>
                <select v-model="createForm.role" class="w-full rounded-md border bg-background px-2 py-1 text-foreground">
                    <option v-for="r in roles" :key="r" :value="r">{{ $t('admin.users.roles.' + r) }}</option>
                </select>
                <InputError :message="createForm.errors.role" />
            </div>
            <div>
                <input
                    id="new-blog-quota"
                    v-model.number="createForm.blog_quota"
                    :disabled="!canEditNewQuota"
                    :placeholder="$t('admin.users.create.blog_quota')"
                    class="w-full rounded-md border bg-background px-2 py-1 text-foreground"
                    min="0"
                    type="number"
                />
                <InputError :message="createForm.errors.blog_quota" />
            </div>
            <div class="flex items-center">
                <Button :disabled="createForm.processing" size="sm" type="submit" variant="constructive">
                    {{ $t('admin.users.actions.create') }}
                </Button>
            </div>
        </form>
    </div>
</template>
