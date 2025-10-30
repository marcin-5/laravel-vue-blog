<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Post;
use Illuminate\Support\Str;

class SeoService
{
    public function generateMetaDescription(string $html, int $limit = 160): string
    {
        $text = strip_tags($html);

        // Convert Markdown leftovers and remove formatting
        $patterns = [
            '/!\[([^]]*)]\([^)]+\)/' => '$1',    // images -> alt
            '/\[([^]]+)]\([^)]+\)/' => '$1',     // links -> text
            '/(\*\*|__|\*|_|`)/' => '',          // emphasis markers
            '/^\s{0,3}[#>\-]+\s*/m' => '',       // heading/blockquote/list markers
        ];
        $text = preg_replace(array_keys($patterns), array_values($patterns), $text);

        // Collapse whitespace and decode HTML entities
        $text = Str::squish(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $text = mb_substr($text, 0, $limit);
        $cutPosition = mb_strrpos($text, ' ');

        if ($cutPosition !== false && $cutPosition > 80) {
            $text = mb_substr($text, 0, $cutPosition);
        }

        return rtrim($text) . '…';
    }

    public function generateBlogStructuredData(Blog $blog, array $posts, string $baseUrl, string $description): array
    {
        $blogUrl = $baseUrl . '/blogs/' . $blog->slug;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => $blog->name,
            'url' => $blogUrl,
            'description' => $description,
            'author' => [
                '@type' => 'Organization',
                'name' => $blog->name,
            ],
            'blogPost' => collect($posts)->map(fn($post) => [
                '@type' => 'BlogPosting',
                'headline' => $post->title,
                'url' => $baseUrl . '/blogs/' . $blog->slug . '/' . $post->slug,
                'datePublished' => $post->published_at?->toIso8601String(),
                'description' => $post->excerpt ? strip_tags($post->excerpt) : null,
            ])->all(),
            'breadcrumb' => $this->generateBreadcrumbStructuredData($baseUrl, $blogUrl, $blog->name),
        ];
    }

    private function generateBreadcrumbStructuredData(
        string $baseUrl,
        string $blogUrl,
        string $blogName,
        ?string $postUrl = null,
        ?string $postTitle = null,
    ): array {
        $items = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'item' => [
                    '@id' => $baseUrl,
                    'name' => config('app.name'),
                ],
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'item' => [
                    '@id' => $blogUrl,
                    'name' => $blogName,
                ],
            ],
        ];

        if ($postUrl && $postTitle) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => 3,
                'item' => [
                    '@id' => $postUrl,
                    'name' => $postTitle,
                ],
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    public function generatePostStructuredData(Blog $blog, Post $post, string $baseUrl, string $description): array
    {
        $blogUrl = $baseUrl . '/blogs/' . $blog->slug;
        $postUrl = $blogUrl . '/' . $post->slug;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $description,
            'url' => $postUrl,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => $blog->name,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $blog->name,
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $postUrl,
            ],
            'breadcrumb' => $this->generateBreadcrumbStructuredData(
                $baseUrl,
                $blogUrl,
                $blog->name,
                $postUrl,
                $post->title,
            ),
        ];
    }
}
