import { useMarkdownPreview } from '@/composables/useMarkdownPreview';
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

    return {
        isPreviewMode,
        isFullPreview,
        previewLayout,
        previewHtml,
        togglePreview,
        toggleFullPreview,
        setLayout,
        handleInput: renderMarkdown,
    };
}
