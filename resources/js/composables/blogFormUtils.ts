import type {
    AdminBlog as Blog,
    BlogFormData,
    BlogTheme,
    AdminGroup as Group,
    GroupFormData,
    AdminPostItem as Post,
    PostFormData,
} from '@/types/blog.types';
import type { InertiaForm } from '@inertiajs/vue3';

export function ensureThemeStructure(theme: BlogTheme | null | undefined): BlogTheme {
    return {
        light: theme?.light ?? {},
        dark: theme?.dark ?? {},
    };
}

export function createDefaultFormData(locale: string = 'en'): BlogFormData {
    return {
        name: '',
        seo_title: null,
        seo_description: null,
        about_seo_description: null,
        contact_seo_description: null,
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
        about: null,
    };
}

export function createFormDataFromBlog(blog: Blog | undefined, defaultLocale: string = 'en'): BlogFormData {
    if (!blog) {
        return createDefaultFormData(defaultLocale);
    }
    return {
        name: blog.name,
        seo_title: blog.seo_title ?? null,
        seo_description: blog.seo_description ?? null,
        about_seo_description: blog.about_seo_description ?? null,
        contact_seo_description: blog.contact_seo_description ?? null,
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
        about: blog.about ?? null,
    };
}

export function populateFormFromBlog(form: InertiaForm<BlogFormData>, blog: Blog, defaultLocale: string = 'en'): void {
    const data = createFormDataFromBlog(blog, defaultLocale);
    form.name = data.name;
    form.seo_title = data.seo_title;
    form.seo_description = data.seo_description;
    form.about_seo_description = data.about_seo_description;
    form.contact_seo_description = data.contact_seo_description;
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
    form.about = data.about;
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

export function populateFormFromGroup(form: InertiaForm<GroupFormData>, group: Group, defaultLocale: string = 'en'): void {
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

export function createDefaultPostFormData(blogId?: number): PostFormData {
    return {
        blog_id: blogId ?? 0,
        group_id: 0,
        title: '',
        seo_title: '',
        excerpt: '',
        summary: '',
        content: '',
        is_published: false,
        visibility: 'public',
        related_posts: [],
        external_links: [],
        tags: [],
    };
}

export function createFormDataFromPost(post: Post | undefined, blogId?: number): PostFormData {
    if (!post) {
        return createDefaultPostFormData(blogId);
    }
    return {
        blog_id: post.blog_id ?? blogId ?? 0,
        group_id: post.group_id ?? 0,
        title: post.title,
        seo_title: post.seo_title ?? '',
        excerpt: post.excerpt ?? '',
        summary: post.summary ?? '',
        content: post.content ?? '',
        is_published: post.is_published,
        visibility: post.visibility ?? 'public',
        related_posts: post.related_posts ?? [],
        external_links: post.external_links ?? [],
        tags: (post.tags ?? []).map((tag) => tag.slug),
    };
}

export function populateFormFromPost(form: InertiaForm<PostFormData>, post: Post): void {
    const data = createFormDataFromPost(post);
    form.blog_id = data.blog_id;
    form.group_id = data.group_id;
    form.title = data.title;
    form.seo_title = data.seo_title;
    form.excerpt = data.excerpt;
    form.summary = data.summary;
    form.content = data.content;
    form.is_published = data.is_published;
    form.visibility = data.visibility;
    form.related_posts = data.related_posts;
    form.external_links = data.external_links;
    form.tags = data.tags;
}
