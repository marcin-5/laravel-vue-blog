import type { AdminBlog as Blog, AdminGroup as Group } from '@/types/blog.types';
import { describe, expect, it } from 'vitest';
import {
    createDefaultFormData,
    createDefaultGroupFormData,
    createFormDataFromBlog,
    createFormDataFromGroup,
    populateFormFromBlog,
    populateFormFromGroup,
} from '../blogFormUtils';

describe('blogFormUtils', () => {
    describe('createDefaultFormData', () => {
        it('returns default form data with provided locale', () => {
            const data = createDefaultFormData('pl');
            expect(data.locale).toBe('pl');
            expect(data.name).toBe('');
            expect(data.seo_title).toBe(null);
            expect(data.is_published).toBe(false);
            expect(data.allow_multiple_tag_filtering).toBe(false);
            expect(data).not.toHaveProperty('theme');
        });

        it('uses "en" as default locale', () => {
            const data = createDefaultFormData();
            expect(data.locale).toBe('en');
        });
    });

    describe('createFormDataFromBlog', () => {
        it('returns default data if blog is undefined', () => {
            expect(createFormDataFromBlog(undefined, 'fr')).toEqual(createDefaultFormData('fr'));
        });

        it('maps blog properties correctly', () => {
            const blog: Partial<Blog> = {
                id: 1,
                name: 'Test Blog',
                seo_title: 'SEO Title',
                description: 'Desc',
                footer: 'Footer',
                motto: 'Motto',
                is_published: true,
                allow_multiple_tag_filtering: true,
                locale: 'de',
                sidebar: 1,
                page_size: 20,
                categories: [{ id: 10, name: 'Cat 1' }] as any,
                theme: { light: { '--bg': '#eee' }, dark: {} },
                landing_page: { content: 'Landing Content' } as any,
            };

            const data = createFormDataFromBlog(blog as Blog);

            expect(data).toEqual({
                name: 'Test Blog',
                seo_title: 'SEO Title',
                seo_description: null,
                about_seo_description: null,
                contact_seo_description: null,
                description: 'Desc',
                footer: 'Footer',
                motto: 'Motto',
                is_published: true,
                allow_multiple_tag_filtering: true,
                locale: 'de',
                sidebar: 1,
                page_size: 20,
                categories: [10],
                landing_content: 'Landing Content',
                about: null,
            });
        });

        it('uses default locale if blog locale is missing', () => {
            const blog: Partial<Blog> = { name: 'Test' };
            const data = createFormDataFromBlog(blog as Blog, 'es');
            expect(data.locale).toBe('es');
        });
    });

    describe('populateFormFromBlog', () => {
        it('populates form from blog data', () => {
            const form = {
                name: '',
                seo_title: null,
                description: '',
                footer: '',
                motto: '',
                is_published: false,
                allow_multiple_tag_filtering: false,
                locale: '',
                sidebar: 0,
                page_size: 0,
                categories: [],
                landing_content: '',
            } as any;

            const blog: Partial<Blog> = {
                name: 'Updated Name',
                seo_title: 'Updated SEO',
                is_published: true,
                allow_multiple_tag_filtering: true,
                locale: 'it',
            };

            populateFormFromBlog(form, blog as Blog);

            expect(form.name).toBe('Updated Name');
            expect(form.seo_title).toBe('Updated SEO');
            expect(form.is_published).toBe(true);
            expect(form.allow_multiple_tag_filtering).toBe(true);
            expect(form.locale).toBe('it');
        });
    });

    describe('createDefaultGroupFormData', () => {
        it('returns default group form data', () => {
            const data = createDefaultGroupFormData('pl');
            expect(data.locale).toBe('pl');
            expect(data.name).toBe('');
            expect(data.allow_registration).toBe(false);
            expect(data).not.toHaveProperty('theme');
        });
    });

    describe('createFormDataFromGroup', () => {
        it('returns default data if group is undefined', () => {
            expect(createFormDataFromGroup(undefined, 'fr')).toEqual(createDefaultGroupFormData('fr'));
        });

        it('maps group properties correctly', () => {
            const group: Partial<Group> = {
                id: 1,
                name: 'Test Group',
                content: 'Content',
                footer: 'Footer',
                is_published: true,
                allow_registration: true,
                locale: 'de',
                sidebar: 1,
                page_size: 20,
            };

            const data = createFormDataFromGroup(group as Group);

            expect(data).toEqual({
                name: 'Test Group',
                content: 'Content',
                footer: 'Footer',
                is_published: true,
                allow_registration: true,
                locale: 'de',
                sidebar: 1,
                page_size: 20,
            });
        });
    });

    describe('populateFormFromGroup', () => {
        it('populates form from group data', () => {
            const form = {
                name: '',
                content: '',
                footer: '',
                is_published: false,
                allow_registration: false,
                locale: '',
                sidebar: 0,
                page_size: 0,
            } as any;

            const group: Partial<Group> = {
                name: 'Updated Group',
                is_published: true,
                allow_registration: true,
                locale: 'it',
            };

            populateFormFromGroup(form, group as Group);

            expect(form.name).toBe('Updated Group');
            expect(form.is_published).toBe(true);
            expect(form.allow_registration).toBe(true);
            expect(form.locale).toBe('it');
        });
    });
});
