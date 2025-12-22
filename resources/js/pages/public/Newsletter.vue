<script lang="ts" setup>
import PublicNavbar from '@/components/PublicNavbar.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    blogs: Array<{ id: number; name: string; slug: string }>;
    selectedBlogId?: number | string;
    userEmail?: string;
}>();

const DEFAULT_FREQUENCY = 'daily';

const newsletterForm = useForm({
    email: props.userEmail || '',
    blog_ids: props.selectedBlogId ? [Number(props.selectedBlogId)] : [],
    frequency: DEFAULT_FREQUENCY,
});

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

const submit = () => {
    if (hasSelectedBlogs.value) {
        newsletterForm.post(route('newsletter.store'), {
            onSuccess: () => {
                // Toast logic handled globally or by flash message
            },
        });
    }
};
</script>

<template>
    <Head title="Newsletter" />
    <div class="flex min-h-screen flex-col bg-primary-foreground text-primary">
        <PublicNavbar maxWidth="max-w-screen-lg" />

        <main class="mx-auto w-full max-w-screen-lg p-4 sm:px-12 md:px-16">
            <div class="mx-auto max-w-2xl py-12">
                <h1 class="mb-6 text-3xl font-bold text-accent-foreground">Zapisz się do newslettera</h1>
                <p class="mb-8 text-slate-600 dark:text-slate-400">Wybierz blogi, z których chcesz otrzymywać powiadomienia o nowych wpisach.</p>

                <form class="space-y-8" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300" for="email">Twój adres e-mail</Label>
                        <Input id="email" v-model="newsletterForm.email" placeholder="email@example.com" required type="email" />
                        <p v-if="newsletterForm.errors.email" class="text-sm text-error">{{ newsletterForm.errors.email }}</p>
                    </div>

                    <div class="space-y-4">
                        <Label class="text-slate-700 dark:text-slate-300">Wybierz blogi</Label>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div v-for="blog in blogs" :key="blog.id" class="flex items-center space-x-2">
                                <Checkbox
                                    :id="'blog-' + blog.id"
                                    :modelValue="newsletterForm.blog_ids.includes(blog.id)"
                                    class="text-primary"
                                    @update:modelValue="(val: boolean | string) => toggleBlogSelection(blog.id, val)"
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

                    <Button
                        :disabled="newsletterForm.processing || !hasSelectedBlogs"
                        :variant="hasSelectedBlogs ? 'outline' : 'muted'"
                        class="w-full"
                        type="submit"
                    >
                        {{ newsletterForm.processing ? 'Zapisywanie...' : 'Zapisz się' }}
                    </Button>
                </form>
            </div>
        </main>
    </div>
</template>
