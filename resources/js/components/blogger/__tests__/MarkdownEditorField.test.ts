import MarkdownEditorField from '@/components/blogger/MarkdownEditorField.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';

describe('MarkdownEditorField', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('renders the label and preview controls in the field header and changes textarea rows', async () => {
        const wrapper = mount(MarkdownEditorField, {
            props: {
                id: 'description',
                storageKey: 'blog.description',
                label: 'Description',
                modelValue: '',
                previewMode: false,
                previewLabel: 'Preview',
                closePreviewLabel: 'Close preview',
                decreaseRowsLabel: 'Decrease rows',
                increaseRowsLabel: 'Increase rows',
            },
        });

        expect(wrapper.find('label').text()).toBe('Description');
        expect(wrapper.find('button[aria-label="Decrease rows"]').attributes('title')).toBe('Decrease rows');
        expect((wrapper.find('textarea').element as HTMLTextAreaElement).rows).toBe(5);

        await wrapper.find('button[aria-label="Increase rows"]').trigger('click');

        expect((wrapper.find('textarea').element as HTMLTextAreaElement).rows).toBe(6);
        expect(localStorage.getItem('markdown-textarea-rows:blog.description')).toBe('6');
    });
});