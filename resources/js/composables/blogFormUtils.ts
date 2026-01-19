// New file: resources/js/composables/blogFormUtils.ts
import type { AdminBlog as Blog, AdminGroup as Group, BlogFormData, BlogTheme, GroupFormData } from '@/types/blog.types';
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
        landing_content: null,
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
        landing_content: blog.landing_page?.content ?? null,
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
    form.landing_content = data.landing_content;
}

export function createDefaultGroupFormData(locale: string = 'en'): GroupFormData {
    return {
        name: '',
        content: null,
        footer: null,
        is_published: false,
        locale,
        sidebar: 0,
        page_size: 10,
        theme: ensureThemeStructure(null),
    };
}

export function createFormDataFromGroup(group: Group | undefined, defaultLocale: string = 'en'): GroupFormData {
    if (!group) {
        return createDefaultGroupFormData(defaultLocale);
    }
    return {
        name: group.name,
        content: group.content ?? null,
        footer: group.footer ?? null,
        is_published: group.is_published,
        locale: group.locale || defaultLocale,
        sidebar: group.sidebar ?? 0,
        page_size: group.page_size ?? 10,
        theme: ensureThemeStructure(group.theme),
    };
}

export function populateFormFromGroup(form: ReturnType<typeof useForm<GroupFormData>>, group: Group, defaultLocale: string = 'en'): void {
    const data = createFormDataFromGroup(group, defaultLocale);
    form.name = data.name;
    form.content = data.content;
    form.footer = data.footer;
    form.is_published = data.is_published;
    form.locale = data.locale;
    form.sidebar = data.sidebar;
    form.page_size = data.page_size;
    form.theme = data.theme;
}
