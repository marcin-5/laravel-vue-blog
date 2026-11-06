import FullScreenPreview from '@/components/FullScreenPreview.vue';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';

const previewProps = {
    content: '',
    previewHtml: '',
    previewLayout: 'vertical' as const,
    saveButtonLabel: 'Save',
    cancelButtonLabel: 'Cancel',
    toggleLayoutButtonLabel: 'Toggle layout',
    exitPreviewButtonLabel: 'Exit preview',
    previewModeTitleLabel: 'Preview mode',
    markdownLabel: 'Markdown',
    previewLabel: 'Preview',
    isEdit: true,
};

const DialogHarness = defineComponent({
    setup() {
        const isOpen = ref(true);

        return () =>
            h(
                Dialog,
                {
                    open: isOpen.value,
                    'onUpdate:open': (open: boolean) => {
                        isOpen.value = open;
                    },
                },
                {
                    default: () =>
                        h(DialogContent, null, {
                            default: () => [
                                h(DialogTitle, null, { default: () => 'Tag content' }),
                                h(DialogDescription, null, { default: () => 'Tag content description' }),
                                h(FullScreenPreview, previewProps),
                            ],
                        }),
                },
            );
    },
});

describe('FullScreenPreview', () => {
    it.each(['pointerdown', 'focusin', 'click'] as const)('does not close dialog on %s inside preview', async (eventName) => {
        const wrapper = mount(DialogHarness, { attachTo: document.body });

        try {
            await new Promise((resolve) => setTimeout(resolve, 0));

            const editor = document.getElementById('fullscreen-markdown');
            expect(editor).not.toBeNull();
            editor?.dispatchEvent(new Event(eventName, { bubbles: true }));

            await nextTick();
            await new Promise((resolve) => setTimeout(resolve, 50));

            expect(document.querySelector('[data-slot="dialog-content"]')).not.toBeNull();
        } finally {
            wrapper.unmount();
        }
    });

    it('keeps the preview interactive while the modal dialog blocks body pointer events', async () => {
        const wrapper = mount(DialogHarness, { attachTo: document.body });

        try {
            await new Promise((resolve) => setTimeout(resolve, 0));

            const previewRoot = document.getElementById('fullscreen-markdown')?.closest('.inset-0');
            expect(previewRoot).not.toBeNull();
            expect(previewRoot?.classList.contains('pointer-events-auto')).toBe(true);
        } finally {
            wrapper.unmount();
        }
    });
});
