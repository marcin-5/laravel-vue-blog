import type { AdminBlog as Blog, BlogFormData } from '@/types/blog.types';
import { createDefaultFormData, populateFormFromBlog } from './blogFormUtils';
import { useEntityForm } from './useEntityForm';

export function useBlogForm() {
    return useEntityForm<Blog, BlogFormData>({
        createDefaultData: (locale) => createDefaultFormData(locale),
        populateFromEntity: populateFormFromBlog,
        storeRoute: route('blogs.store'),
        updateRoute: (id) => route('blogs.update', id),
    });
}
