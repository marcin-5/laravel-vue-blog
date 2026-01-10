import axios from 'axios';
import { ref, type Ref } from 'vue';

type PreviewLayout = 'horizontal' | 'vertical';

export function useMarkdownPreview(routeName: string = 'markdown.preview') {
    const isPreviewMode = ref(false);
    const isFullPreview = ref(false);
    const previewLayout = ref<PreviewLayout>('vertical');
    const previewHtml = ref('');
    const MARKDOWN_RENDER_ERROR_HTML = '<p class="text-error">Error rendering markdown</p>';

    async function fetchMarkdownPreview(content: string): Promise<string> {
        try {
            const response = await axios.post(
                route(routeName),
                {
                    content,
                },
                {
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                },
            );
            return response.data.html;
        } catch (error) {
            console.error('Markdown preview fetch error:', error);
            throw new Error('Failed to fetch markdown preview');
        }
    }

    async function renderMarkdown(content: string) {
        if (!content) {
            previewHtml.value = '';
            return;
        }
        try {
            previewHtml.value = await fetchMarkdownPreview(content);
        } catch (error) {
            console.error('Failed to render markdown:', error);
            previewHtml.value = MARKDOWN_RENDER_ERROR_HTML;
        }
    }

    async function toggleModeAndRender(mode: Ref<boolean>, content: string) {
        mode.value = !mode.value;
        if (mode.value) {
            await renderMarkdown(content);
        }
    }

    async function togglePreview(content: string) {
        await toggleModeAndRender(isPreviewMode, content);
    }

    async function toggleFullPreview(content: string) {
        await toggleModeAndRender(isFullPreview, content);
    }

    function setLayout(layout: PreviewLayout) {
        previewLayout.value = layout;
    }

    return {
        isPreviewMode,
        isFullPreview,
        previewLayout,
        previewHtml,
        renderMarkdown,
        togglePreview,
        toggleFullPreview,
        setLayout,
    };
}
