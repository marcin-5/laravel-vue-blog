import BlogSeoFields from '@/components/blogger/BlogSeoFields.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/components/blogger/PostFormField.vue', () => ({
    default: {
        name: 'PostFormField',
        props: ['id'],
        template: '<div class="post-form-field" :data-field-id="id" />',
    },
}));

const form = {
    name: '',
    seo_title: null,
    seo_description: null,
    about_seo_description: null,
    contact_seo_description: null,
    motto: null,
    errors: {},
};

const translations = {
    name: 'Name',
    namePlaceholder: 'Name placeholder',
    seoTitle: 'SEO title',
    seoTitlePlaceholder: 'SEO title placeholder',
    seoDescription: 'SEO description',
    seoDescriptionPlaceholder: 'SEO description placeholder',
    aboutSeoDescription: 'About SEO description',
    aboutSeoDescriptionPlaceholder: 'About SEO description placeholder',
    contactSeoDescription: 'Contact SEO description',
    contactSeoDescriptionPlaceholder: 'Contact SEO description placeholder',
    motto: 'Motto',
    mottoPlaceholder: 'Motto placeholder',
    mottoTooltip: 'Motto tooltip',
    characters: 'characters',
};

describe('BlogSeoFields', () => {
    it('renders all SEO fields with the configured prefix', () => {
        const wrapper = mount(BlogSeoFields, {
            props: {
                fieldIdPrefix: 'create-blog-new',
                form: form as never,
                translations,
            },
        });

        expect(wrapper.findAll('.post-form-field')).toHaveLength(6);
        expect(wrapper.find('[data-field-id="create-blog-new-name"]').exists()).toBe(true);
        expect(wrapper.find('[data-field-id="create-blog-new-contact-seo-description"]').exists()).toBe(true);
        expect(wrapper.find('[data-field-id="create-blog-new-motto"]').exists()).toBe(true);
    });
});
