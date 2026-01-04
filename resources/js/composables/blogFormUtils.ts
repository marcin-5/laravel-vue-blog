// New file: resources/js/composables/blogFormUtils.ts
import type { AdminBlog as Blog, BlogFormData, BlogTheme } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';

export function ensureThemeStructure(theme: BlogTheme | null | undefined): BlogTheme {
    return {
        light: theme?.light ?? {},
        dark: theme?.dark ?? {},
    };
}

export function createDefaultFormData(locale: string = 'en'): BlogFormData {
    return {
        name: '',
        description: null,
        footer: null,
        motto: null,
        is_published: false,
        locale,
        categories: [],
        sidebar: 0,
        page_size: 10,
        theme: ensureThemeStructure(null),
    };
}

export function createFormDataFromBlog(blog: Blog | undefined, defaultLocale: string = 'en'): BlogFormData {
    if (!blog) {
        return createDefaultFormData(defaultLocale);
    }
    return {
        name: blog.name,
        description: blog.description ?? null,
        footer: blog.footer ?? null,
        motto: blog.motto ?? null,
        is_published: blog.is_published,
        locale: blog.locale || defaultLocale,
        sidebar: blog.sidebar ?? 0,
        page_size: blog.page_size ?? 10,
        categories: (blog.categories ?? []).map((c) => c.id),
        theme: ensureThemeStructure(blog.theme),
    };
}

export function populateFormFromBlog(form: ReturnType<typeof useForm<BlogFormData>>, blog: Blog, defaultLocale: string = 'en'): void {
    const data = createFormDataFromBlog(blog, defaultLocale);
    form.name = data.name;
    form.description = data.description;
    form.footer = data.footer;
    form.motto = data.motto;
    form.is_published = data.is_published;
    form.locale = data.locale;
    form.sidebar = data.sidebar;
    form.page_size = data.page_size;
    form.categories = data.categories;
    form.theme = data.theme;
}
