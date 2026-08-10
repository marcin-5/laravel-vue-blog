import BlogContentFields from '@/components/blogger/BlogContentFields.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/components/blogger/EntityMarkdownField.vue', () => ({
    default: {
        name: 'EntityMarkdownField',
        props: ['id'],
        template: '<button class="markdown-field" :data-field-id="id" type="button" @click="$emit(\'cancel\')" />',
    },
}));

const form = {
    description: null,
    landing_content: null,
    about: null,
    footer: null,
    errors: {},
    processing: false,
};

const markdownTranslations = {
    cancel: 'Cancel',
    create: 'Create',
    save: 'Save',
    exitPreview: 'Exit preview',
    markdownLabel: 'Markdown',
    previewLabel: 'Preview',
    previewModeTitle: 'Preview mode',
    toggleLayout: 'Toggle layout',
    closePreview: 'Close preview',
    preview: 'Preview',
    fullPreview: 'Full preview',
    splitView: 'Split view',
};

const translations = {
    description: { label: 'Description', placeholder: 'Description placeholder', markdown: markdownTranslations },
    landingContent: { label: 'Landing content', placeholder: 'Landing placeholder', markdown: markdownTranslations },
    about: { label: 'About', placeholder: 'About placeholder', markdown: markdownTranslations },
    footer: { label: 'Footer', placeholder: 'Footer placeholder', markdown: markdownTranslations },
};

describe('BlogContentFields', () => {
    it('renders all content fields with the configured prefix', () => {
        const wrapper = mount(BlogContentFields, {
            props: {
                fieldIdPrefix: 'edit-blog-1',
                form: form as never,
                isEdit: true,
                translations,
            },
        });

        expect(wrapper.findAll('.markdown-field')).toHaveLength(4);
        expect(wrapper.find('[data-field-id="edit-blog-1-description"]').exists()).toBe(true);
        expect(wrapper.find('[data-field-id="edit-blog-1-landing-content"]').exists()).toBe(true);
        expect(wrapper.find('[data-field-id="edit-blog-1-about"]').exists()).toBe(true);
        expect(wrapper.find('[data-field-id="edit-blog-1-footer"]').exists()).toBe(true);
    });

    it('forwards cancel from a content field', async () => {
        const wrapper = mount(BlogContentFields, {
            props: {
                fieldIdPrefix: 'create-blog-new',
                form: form as never,
                isEdit: false,
                translations,
            },
        });

        await wrapper.find('.markdown-field').trigger('click');

        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });
});
