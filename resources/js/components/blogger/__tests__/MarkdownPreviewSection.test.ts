import MarkdownPreviewSection from '@/components/blogger/MarkdownPreviewSection.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const translations = {
    cancel: 'Cancel',
    create: 'Create',
    save: 'Save',
    exitPreview: 'Exit preview',
    markdownLabel: 'Markdown',
    previewLabel: 'Preview',
    previewModeTitle: 'Preview mode',
    horizontalLayout: 'Horizontal',
    verticalLayout: 'Vertical',
    closePreview: 'Close preview',
    preview: 'Preview',
    fullPreview: 'Full preview',
    splitView: 'Split view',
    decreaseRows: 'Decrease textarea rows',
    increaseRows: 'Increase textarea rows',
    decreasePreviewHeight: 'Decrease preview height',
    increasePreviewHeight: 'Increase preview height',
};

function mountSection(previewLayout: 'vertical' | 'horizontal', isFullPreview = false) {
    return mount(MarkdownPreviewSection, {
        props: {
            id: 'landing-markdown',
            storageKey: 'blog.landing_content',
            label: 'Content',
            modelValue: '# Hello',
            isPreviewMode: true,
            isFullPreview,
            previewLayout,
            previewHtml: '<h1>Hello</h1>',
            translations,
        },
    });
}

describe('MarkdownPreviewSection', () => {
    it('shows the label of the layout the button switches to', async () => {
        const wrapper = mountSection('vertical');

        const layoutButton = wrapper.findAll('button').find((button) => button.text() === 'Horizontal');
        expect(layoutButton).toBeDefined();

        await wrapper.setProps({ previewLayout: 'horizontal' });

        expect(wrapper.findAll('button').some((button) => button.text() === 'Vertical')).toBe(true);
        expect(wrapper.findAll('button').some((button) => button.text() === 'Horizontal')).toBe(false);
    });

    it('emits the typed value with the input event so the preview renders the latest text', async () => {
        const wrapper = mountSection('vertical');

        await wrapper.find('#landing-markdown').setValue('**bold**');

        expect(wrapper.emitted('input')).toEqual([['**bold**']]);
    });

    it('shows preview actions in the header beside the preview label', () => {
        const wrapper = mountSection('vertical');
        const previewHeader = wrapper.find('h3').element.parentElement;

        expect(previewHeader?.textContent).toContain('Preview');
        expect(previewHeader?.textContent).toContain('Full preview');
        expect(previewHeader?.textContent).toContain('Horizontal');
        expect(previewHeader?.querySelectorAll('button')).toHaveLength(2);
    });

    it('shows temporary preview height controls only in the stacked layout', async () => {
        const wrapper = mountSection('horizontal');

        expect(wrapper.findAll('button').some((button) => button.attributes('aria-label') === 'Increase preview height')).toBe(true);

        await wrapper.setProps({ previewLayout: 'vertical' });

        expect(wrapper.findAll('button').some((button) => button.attributes('aria-label') === 'Increase preview height')).toBe(false);
    });

    it('keeps the preview toggle available in the editor header', async () => {
        const wrapper = mountSection('vertical');

        await wrapper.findAll('button').find((button) => button.text() === 'Close preview')?.trigger('click');

        expect(wrapper.emitted('togglePreview')).toEqual([['# Hello']]);
    });

    it('emits the opposite layout when the layout button is clicked', async () => {
        const wrapper = mountSection('vertical');

        await wrapper.findAll('button').find((button) => button.text() === 'Horizontal')?.trigger('click');

        expect(wrapper.emitted('setLayout')).toEqual([['horizontal']]);
    });

    it('updates the layout label in the full preview', async () => {
        const wrapper = mountSection('vertical', true);

        expect(document.body.textContent).toContain('Horizontal');

        await wrapper.setProps({ previewLayout: 'horizontal' });

        expect(document.body.textContent).toContain('Vertical');

        wrapper.unmount();
    });
});
