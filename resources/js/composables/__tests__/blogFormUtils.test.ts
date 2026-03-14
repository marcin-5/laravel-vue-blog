import type { AdminBlog as Blog, AdminGroup as Group } from '@/types/blog.types';
import { describe, expect, it } from 'vitest';
import {
    createDefaultFormData,
    createDefaultGroupFormData,
    createFormDataFromBlog,
    createFormDataFromGroup,
    ensureThemeStructure,
    populateFormFromBlog,
    populateFormFromGroup
} from '../blogFormUtils';

describe('blogFormUtils', () => {
    describe('ensureThemeStructure', () => {
        it('returns default structure when theme is null or undefined', () => {
            expect(ensureThemeStructure(null)).toEqual({ light: {}, dark: {} });
            expect(ensureThemeStructure(undefined)).toEqual({ light: {}, dark: {} });
        });

        it('preserves existing theme structure', () => {
            const theme = {
                light: { '--primary': '#ffd020' },
                dark: { '--primary': '#b0ff20' },
            };
            expect(ensureThemeStructure(theme)).toEqual(theme);
        });

        it('fills missing parts of theme structure', () => {
            const partialTheme = { light: { '--primary': '#ffb020' } } as any;
            expect(ensureThemeStructure(partialTheme)).toEqual({
                light: { '--primary': '#ffb020' },
                dark: {},
            });
        });
    });

    describe('createDefaultFormData', () => {
        it('returns default form data with provided locale', () => {
            const data = createDefaultFormData('pl');
            expect(data.locale).toBe('pl');
            expect(data.name).toBe('');
            expect(data.is_published).toBe(false);
            expect(data.theme).toEqual({ light: {}, dark: {} });
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
                description: 'Desc',
                footer: 'Footer',
                motto: 'Motto',
                is_published: true,
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
                description: 'Desc',
                footer: 'Footer',
                motto: 'Motto',
                is_published: true,
                locale: 'de',
                sidebar: 1,
                page_size: 20,
                categories: [10],
                theme: { light: { '--bg': '#eee' }, dark: {} },
                landing_content: 'Landing Content',
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
                description: '',
                footer: '',
                motto: '',
                is_published: false,
                locale: '',
                sidebar: 0,
                page_size: 0,
                categories: [],
                theme: null,
                landing_content: '',
            } as any;

            const blog: Partial<Blog> = {
                name: 'Updated Name',
                is_published: true,
                locale: 'it',
            };

            populateFormFromBlog(form, blog as Blog);

            expect(form.name).toBe('Updated Name');
            expect(form.is_published).toBe(true);
            expect(form.locale).toBe('it');
        });
    });

    describe('createDefaultGroupFormData', () => {
        it('returns default group form data', () => {
            const data = createDefaultGroupFormData('pl');
            expect(data.locale).toBe('pl');
            expect(data.name).toBe('');
            expect(data.theme).toEqual({ light: {}, dark: {} });
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
                locale: 'de',
                sidebar: 1,
                page_size: 20,
                theme: { light: { '--bg': '#eee' }, dark: {} },
            };

            const data = createFormDataFromGroup(group as Group);

            expect(data).toEqual({
                name: 'Test Group',
                content: 'Content',
                footer: 'Footer',
                is_published: true,
                locale: 'de',
                sidebar: 1,
                page_size: 20,
                theme: { light: { '--bg': '#eee' }, dark: {} },
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
                locale: '',
                sidebar: 0,
                page_size: 0,
                theme: null,
            } as any;

            const group: Partial<Group> = {
                name: 'Updated Group',
                is_published: true,
                locale: 'it',
            };

            populateFormFromGroup(form, group as Group);

            expect(form.name).toBe('Updated Group');
            expect(form.is_published).toBe(true);
            expect(form.locale).toBe('it');
        });
    });
});
