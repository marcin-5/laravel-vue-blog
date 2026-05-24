import { useMarkdownPreview, type MarkdownPreviewSection } from '@/composables/useMarkdownPreview';

export type { MarkdownPreviewSection };

export function useMarkdownPreviewSection(routeName: string = 'markdown.preview'): MarkdownPreviewSection {
    return useMarkdownPreview(routeName);
}
