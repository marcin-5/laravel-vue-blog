<script lang="ts" setup>
import PublicNavbar from '@/components/PublicNavbar.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/composables/useToast';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    mode: 'subscribe' | 'manage';
    blogs: Array<{ id: number; name: string; slug: string }>;
    // subscribe
    selectedBlogId?: number | string;
    userEmail?: string;
    // manage
    email?: string;
    currentSubscriptions?: number[];
    frequency?: string;
    updateUrl?: string;
    unsubscribeUrl?: string;
}>();

const DEFAULT_FREQUENCY = 'daily';

const isManageMode = computed(() => props.mode === 'manage');
const displayEmail = computed(() => (props.mode === 'subscribe' ? props.userEmail || '' : props.email || ''));
const initialBlogIds = computed(() => {
    if (isManageMode.value) return props.currentSubscriptions || [];
    return props.selectedBlogId ? [Number(props.selectedBlogId)] : [];
});
const initialFrequency = computed(() => (isManageMode.value ? props.frequency || DEFAULT_FREQUENCY : DEFAULT_FREQUENCY));
const submitUrl = computed(() => (isManageMode.value ? props.updateUrl! : route('newsletter.store')));
const submitText = computed(() =>
    isManageMode.value
        ? newsletterForm.processing
            ? 'Zapisywanie...'
            : 'Zaktualizuj ustawienia'
        : newsletterForm.processing
          ? 'Zapisywanie...'
          : 'Zapisz się',
);
const title = computed(() => (isManageMode.value ? 'Zarządzaj subskrypcją newslettera' : 'Newsletter'));
const desc = computed(() =>
    isManageMode.value
        ? `Edytuj swoje preferencje dla adresu: <strong>${displayEmail.value}</strong>`
        : 'Wybierz blogi, z których chcesz otrzymywać powiadomienia o nowych wpisach.',
);
const blogLabel = computed(() => (isManageMode.value ? 'Wybrane blogi' : 'Wybierz blogi'));
const successDesc = computed(() =>
    isManageMode.value ? 'Twoje ustawienia newslettera zostały zaktualizowane.' : 'Twoje zgłoszenie do newslettera zostało zapisane.',
);

const newsletterForm = useForm({
    email: displayEmail.value,
    blog_ids: initialBlogIds.value,
    frequency: initialFrequency.value,
});

const unsubscribeForm = useForm({ email: displayEmail.value });

const hasSelectedBlogs = computed(() => newsletterForm.blog_ids.length > 0);

const toggleBlogState = (blogIds: number[], blogId: number, isChecked: boolean | string) => {
    const id = Number(blogId);
    const index = blogIds.indexOf(id);
    if (isChecked && index === -1) {
        blogIds.push(id);
    } else if (!isChecked && index !== -1) {
        blogIds.splice(index, 1);
    }
};

const toggleBlogSelection = (blogId: number | string, checked: boolean | string) => {
    toggleBlogState(newsletterForm.blog_ids, Number(blogId), checked);
};

const { toast } = useToast();

const submit = () => {
    if (hasSelectedBlogs.value || isManageMode.value) {
        newsletterForm.post(submitUrl.value, {
            onSuccess: () => {
                toast({
                    title: 'Sukces!',
                    description: successDesc.value,
                    variant: 'success',
                    size: 'sm',
                });
            },
            onError: () => {
                toast({
                    title: 'Błąd!',
                    description: isManageMode.value
                        ? 'Wystąpił problem podczas aktualizacji ustawień.'
                        : 'Wystąpił problem podczas zapisywania do newslettera.',
                    variant: 'destructive',
                    size: 'sm',
                });
            },
        });
    }
};

const unsubscribe = () => {
    if (confirm('Czy na pewno chcesz zrezygnować z subskrypcji wszystkich blogów?')) {
        unsubscribeForm.post(props.unsubscribeUrl!, {
            onSuccess: () => {
                toast({
                    title: 'Wypisano!',
                    description: 'Zostałeś wypisany z newslettera.',
                    variant: 'success',
                    size: 'sm',
                });
            },
        });
    }
};
</script>

<template>
    <Head :title="title" />
    <div class="flex min-h-screen flex-col bg-primary-foreground text-primary">
        <PublicNavbar maxWidth="max-w-screen-lg" />
        <Toaster />
        <main class="mx-auto w-full max-w-screen-lg p-4 sm:px-12 md:px-16">
            <div class="mx-auto max-w-2xl py-12">
                <h1 class="mb-6 text-3xl font-bold text-accent-foreground">
                    {{ isManageMode ? 'Zarządzaj subskrypcją newslettera' : 'Zapisz się do newslettera' }}
                </h1>
                <p class="mb-8 text-slate-600 dark:text-slate-400" v-html="desc"></p>

                <form class="space-y-8" @submit.prevent="submit">
                    <!-- Email sekcja tylko w subscribe -->
                    <div v-if="!isManageMode" class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300" for="email">Twój adres e-mail</Label>
                        <Input id="email" v-model="newsletterForm.email" placeholder="email@example.com" required type="email" />
                        <p v-if="newsletterForm.errors.email" class="text-sm text-error">{{ newsletterForm.errors.email }}</p>
                    </div>

                    <!-- Blogi -->
                    <div class="space-y-4">
                        <Label class="text-slate-700 dark:text-slate-300">{{ blogLabel }}</Label>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div v-for="blog in blogs" :key="blog.id" class="flex items-center space-x-2">
                                <Checkbox
                                    :id="'blog-' + blog.id"
                                    :modelValue="newsletterForm.blog_ids.includes(blog.id)"
                                    class="text-primary"
                                    @update:modelValue="(val) => toggleBlogSelection(blog.id, val)"
                                />
                                <label
                                    :for="'blog-' + blog.id"
                                    class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    {{ blog.name }}
                                </label>
                            </div>
                        </div>
                        <p v-if="newsletterForm.errors.blog_ids" class="text-sm text-error">{{ newsletterForm.errors.blog_ids }}</p>
                    </div>

                    <!-- Częstotliwość -->
                    <div class="space-y-4">
                        <Label class="text-slate-700 dark:text-slate-300">Częstotliwość powiadomień</Label>
                        <div class="flex flex-col space-y-3">
                            <div class="flex items-center space-x-2">
                                <input
                                    id="daily"
                                    v-model="newsletterForm.frequency"
                                    class="h-4 w-4 border-border text-primary focus:ring-primary"
                                    type="radio"
                                    value="daily"
                                />
                                <Label for="daily">W dniu pojawienia się nowego wpisu</Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input
                                    id="weekly"
                                    v-model="newsletterForm.frequency"
                                    class="h-4 w-4 border-border text-primary focus:ring-primary"
                                    type="radio"
                                    value="weekly"
                                />
                                <Label for="weekly">Raz na tydzień (podsumowanie)</Label>
                            </div>
                        </div>
                        <p v-if="newsletterForm.errors.frequency" class="text-sm text-error">{{ newsletterForm.errors.frequency }}</p>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-col gap-4">
                        <Button
                            :disabled="newsletterForm.processing || (!hasSelectedBlogs && !isManageMode)"
                            :variant="hasSelectedBlogs ? 'outline' : 'muted'"
                            class="w-full"
                            type="submit"
                        >
                            {{ submitText }}
                        </Button>
                        <Button
                            v-if="isManageMode"
                            class="w-full text-destructive hover:bg-destructive/10"
                            type="button"
                            variant="ghost"
                            @click="unsubscribe"
                        >
                            Zrezygnuj z subskrypcji
                        </Button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>
