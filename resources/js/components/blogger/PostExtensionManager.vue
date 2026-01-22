<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import type { AdminPostItem as PostItem } from '@/types/blog.types';
import axios from 'axios';
import { GripVertical, Plus, Trash2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { toast } = useToast();

interface Props {
    post: PostItem;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'updated'): void;
}>();

const availableExtensions = ref<Partial<PostItem>[]>([]);
const selectedExtensionId = ref<string>('');
const isAttaching = ref(false);

async function fetchAvailableExtensions() {
    try {
        const response = await axios.get(route('blogger.posts.extensions.available', { post: props.post.id }));
        availableExtensions.value = response.data;
    } catch (error) {
        console.error('Failed to fetch extensions', error);
    }
}

async function attachExtension() {
    if (!selectedExtensionId.value) return;

    isAttaching.value = true;
    try {
        await axios.post(route('blogger.posts.extensions.attach', { post: props.post.id }), {
            extension_post_id: selectedExtensionId.value,
            display_order: (props.post.extensions?.length || 0) + 1,
        });
        selectedExtensionId.value = '';

        toast({
            title: t('blogger.extensions.attached_success'),
            variant: 'default',
        });

        await fetchAvailableExtensions();
        emit('updated');
    } catch (error) {
        console.error('Failed to attach extension', error);
        toast({
            title: t('blogger.extensions.attach_failed'),
            variant: 'destructive',
        });
    } finally {
        isAttaching.value = false;
    }
}

async function detachExtension(extensionId: number) {
    if (!confirm(t('blogger.extensions.confirm_detach'))) return;

    try {
        await axios.delete(
            route('blogger.posts.extensions.detach', {
                post: props.post.id,
                extensionPostId: extensionId,
            }),
        );

        toast({
            title: t('blogger.extensions.detached_success'),
            variant: 'default',
        });

        await fetchAvailableExtensions();
        emit('updated');
    } catch (error) {
        console.error('Failed to detach extension', error);
        toast({
            title: t('blogger.extensions.detach_failed'),
            variant: 'destructive',
        });
    }
}

onMounted(() => {
    fetchAvailableExtensions();
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-end gap-2">
            <div class="flex-1 space-y-1">
                <label class="text-xs font-medium">{{ t('blogger.extensions.select_label') }}</label>
                <Select v-model="selectedExtensionId">
                    <SelectTrigger>
                        <SelectValue :placeholder="t('blogger.extensions.select_placeholder')" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="ext in availableExtensions" :key="ext.id" :value="ext.id!.toString()">
                            {{ ext.title }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <Button :disabled="!selectedExtensionId || isAttaching" variant="constructive" @click="attachExtension">
                <Plus class="mr-2 h-4 w-4" />
                {{ t('blogger.extensions.add_button') }}
            </Button>
        </div>

        <div v-if="post.extensions && post.extensions.length > 0" class="space-y-2">
            <div v-for="extension in post.extensions" :key="extension.id" class="flex items-center justify-between rounded-md border bg-muted/30 p-2">
                <div class="flex items-center gap-2">
                    <GripVertical class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm">{{ extension.title }}</span>
                </div>
                <Button class="h-8 w-8 text-destructive hover:bg-destructive/10" size="icon" variant="ghost" @click="detachExtension(extension.id)">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </div>
        <div v-else class="py-4 text-center text-sm text-muted-foreground">
            {{ t('blogger.extensions.no_extensions') }}
        </div>
    </div>
</template>
