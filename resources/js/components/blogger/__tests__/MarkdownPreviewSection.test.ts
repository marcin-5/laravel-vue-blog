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
};

function mountSection(previewLayout: 'vertical' | 'horizontal', isFullPreview = false) {
    return mount(MarkdownPreviewSection, {
        props: {
            id: 'landing-markdown',
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
