<?php

namespace App\Services;

use App\Models\Blog;

readonly class BlogContentService
{
    public function __construct(private MarkdownService $markdown)
    {
    }

    public function enrichDescriptionWithAuthor(string $descriptionHtml, Blog $blog): string
    {
        if ($descriptionHtml === '') {
            return $descriptionHtml;
        }

        $ownerLine = $this->buildAuthorLine($blog);

        if (!$ownerLine) {
            return $descriptionHtml;
        }

        return $this->insertAuthorLine($descriptionHtml, $ownerLine);
    }

    private function buildAuthorLine(Blog $blog): ?string
    {
        $ownerName = e($blog->user?->name ?? '');
        $ownerEmail = e($blog->user?->email ?? '');

        if ($ownerName === '' || $ownerEmail === '') {
            return null;
        }

        return sprintf(
            '<p class="author text-md text-slate-700 dark:text-slate-300 mr-12 text-end" style="font-family: \'Noto Serif\', serif"><a href="mailto:%s">%s</a></p>',
            $ownerEmail,
            $ownerName,
        );
    }

    private function insertAuthorLine(string $html, string $ownerLine): string
    {
        $footnotesMarker = '<div class="footnotes">';

        if (str_contains($html, $footnotesMarker)) {
            return str_replace($footnotesMarker, $ownerLine . $footnotesMarker, $html);
        }

        return $html . $ownerLine;
    }
}
