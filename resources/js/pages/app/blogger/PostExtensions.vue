<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import PostExtensionAttachForm from '@/components/blogger/PostExtensionAttachForm.vue';
import PostExtensionList from '@/components/blogger/PostExtensionList.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Extension {
    id: number;
    title: string;
    excerpt?: string | null;
    display_order: number;
}

interface AvailableExtension {
    id: number;
    title: string;
    excerpt?: string | null;
}

interface ExtensionOrder {
    id: number;
    display_order: number;
}

const props = defineProps<{
    post: { id: number; title: string };
    extensions: Extension[];
    availableExtensions: AvailableExtension[];
    context: 'blog' | 'group';
    backUrl: string;
}>();

const { t } = useI18n();
const pageTitle = computed(() => t('blogger.extensions.manage_title', { post: props.post.title }));
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.index'), href: props.backUrl },
    { title: pageTitle.value, href: route('blogger.posts.extensions.index', routeParameters.value) },
]);
const routeParameters = computed(() => ({
    post: props.post.id,
    ...(props.context === 'group' ? { context: 'group' } : {}),
}));

const attachForm = useForm({ extension_post_id: '' });
const detachForm = useForm({});
const orderForm = useForm<{ extensions: ExtensionOrder[] }>({ extensions: [] });
const orderedExtensions = ref<Extension[]>(props.extensions.map((extension) => ({ ...extension })));
const isMutating = computed(() => detachForm.processing || orderForm.processing);

watch(
    () => props.extensions,
    (extensions) => {
        orderedExtensions.value = extensions.map((extension) => ({ ...extension }));
    },
);

function submitAttach() {
    if (!attachForm.extension_post_id) {
        return;
    }

    attachForm.post(route('blogger.posts.extensions.attach', routeParameters.value), {
        only: ['extensions', 'availableExtensions'],
        preserveScroll: true,
        onSuccess: () => attachForm.reset(),
    });
}

function detachExtension(extension: Extension) {
    detachForm.delete(
        route('blogger.posts.extensions.detach', {
            ...routeParameters.value,
            extensionPostId: extension.id,
        }),
        {
            only: ['extensions', 'availableExtensions'],
            preserveScroll: true,
            onBefore: () => window.confirm(t('blogger.extensions.confirm_detach')),
        },
    );
}

function moveExtension(index: number, direction: -1 | 1) {
    const targetIndex = index + direction;

    if (targetIndex < 0 || targetIndex >= orderedExtensions.value.length || orderForm.processing) {
        return;
    }

    const previousOrder = [...orderedExtensions.value];
    const nextOrder = [...previousOrder];
    const [extension] = nextOrder.splice(index, 1);
    nextOrder.splice(targetIndex, 0, extension);
    orderedExtensions.value = nextOrder;
    orderForm.clearErrors();
    orderForm.extensions = nextOrder.map((item, position) => ({
        id: item.id,
        display_order: position + 1,
    }));
    orderForm.put(route('blogger.posts.extensions.reorder', routeParameters.value), {
        only: ['extensions', 'availableExtensions'],
        preserveScroll: true,
        onError: () => {
            orderedExtensions.value = previousOrder;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="pageTitle" />
        <main class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 sm:p-6">
            <header class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                <div class="space-y-2">
                    <p class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">{{ t('blogger.extensions.page_label') }}</p>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ pageTitle }}</h1>
                    <p class="max-w-xl text-sm leading-relaxed text-muted-foreground">{{ t('blogger.extensions.page_description') }}</p>
                </div>
                <Button as-child variant="outline">
                    <Link :href="props.backUrl">
                        <ArrowLeft class="mr-2 size-4" />
                        {{ t('blogger.extensions.back_to_list') }}
                    </Link>
                </Button>
            </header>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold">{{ t('blogger.extensions.available_heading') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ t('blogger.extensions.available_description') }}</p>
                </div>
                <PostExtensionAttachForm
                    :available-extensions="props.availableExtensions"
                    :selected-extension-id="attachForm.extension_post_id"
                    :error="attachForm.errors.extension_post_id"
                    :processing="attachForm.processing"
                    :select-label="t('blogger.extensions.select_label')"
                    :placeholder="t('blogger.extensions.select_placeholder')"
                    :add-label="t('blogger.extensions.add_button')"
                    :no-available-label="t('blogger.extensions.no_available')"
                    @update:selected-extension-id="attachForm.extension_post_id = $event"
                    @submit="submitAttach"
                />
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold">{{ t('blogger.extensions.assigned_heading') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ t('blogger.extensions.assigned_description') }}</p>
                </div>
                <PostExtensionList
                    :extensions="orderedExtensions"
                    :processing="isMutating"
                    :empty-label="t('blogger.extensions.no_extensions')"
                    :move-up-label="t('blogger.extensions.move_up')"
                    :move-down-label="t('blogger.extensions.move_down')"
                    :detach-label="t('blogger.extensions.detach_button')"
                    @move="moveExtension"
                    @detach="detachExtension"
                />
                <InputError :message="orderForm.errors.extensions" />
            </section>
        </main>
    </AppLayout>
</template>
