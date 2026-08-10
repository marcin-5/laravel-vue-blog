import type { AdminBlog as Blog, BlogFormData } from '@/types/blog.types';
import type { Ref } from 'vue';
import { createDefaultFormData, populateFormFromBlog } from './blogFormUtils';
import { useEntityForm } from './useEntityForm';

interface UseBlogFormOptions {
    showCreate?: Ref<boolean>;
}

export function useBlogForm(options: UseBlogFormOptions = {}) {
    return useEntityForm<Blog, BlogFormData>({
        createDefaultData: (locale) => createDefaultFormData(locale),
        populateFromEntity: populateFormFromBlog,
        storeRoute: route('blogs.store'),
        updateRoute: (id) => route('blogs.update', id),
        showCreate: options.showCreate,
    });
}
