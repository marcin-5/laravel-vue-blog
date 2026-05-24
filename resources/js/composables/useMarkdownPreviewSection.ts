import { useMarkdownPreview } from '@/composables/useMarkdownPreview';
import type { MarkdownPreviewSection } from '@/types/blog.types';

export type { MarkdownPreviewSection };

export function useMarkdownPreviewSection(routeName: string = 'markdown.preview'): MarkdownPreviewSection {
    return useMarkdownPreview(routeName);
}
