import { useMarkdownPreview } from '@/composables/useMarkdownPreview';
import { useDebounceFn } from '@vueuse/core';
import type { Ref } from 'vue';

export interface MarkdownPreviewSection {
    isPreviewMode: Ref<boolean>;
    isFullPreview: Ref<boolean>;
    previewLayout: Ref<'horizontal' | 'vertical'>;
    previewHtml: Ref<string>;
    togglePreview: (content: string) => void;
    toggleFullPreview: (content: string) => void;
    setLayout: (layout: 'horizontal' | 'vertical') => void;
    handleInput: (content: string) => void;
}

export function useMarkdownPreviewSection(routeName: string = 'markdown.preview'): MarkdownPreviewSection {
    const { isPreviewMode, isFullPreview, previewLayout, previewHtml, renderMarkdown, togglePreview, toggleFullPreview, setLayout } =
        useMarkdownPreview(routeName);

    const handleInput = useDebounceFn((content: string) => {
        void renderMarkdown(content);
    }, 300);

    return {
        isPreviewMode,
        isFullPreview,
        previewLayout,
        previewHtml,
        togglePreview,
        toggleFullPreview,
        setLayout,
        handleInput,
    };
}
