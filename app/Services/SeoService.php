<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Post;
use DateTimeInterface;
use Illuminate\Support\Str;

class SeoService
{
    private const array MARKDOWN_PATTERNS = [
        '/!\[([^]]*)]\([^)]+\)/' => '$1',    // images -> alt
        '/\[([^]]+)]\([^)]+\)/' => '$1',     // links -> text
        '/(\*\*|__|\*|_|`)/' => '',          // emphasis markers
        '/^\s{0,3}[#>\-]+\s*/m' => '',       // heading/blockquote/list markers
    ];

    public function generateMetaDescription(string $html, int $limit = 160): string
    {
        $text = $this->safeStripTags($html);
        $text = preg_replace(array_keys(self::MARKDOWN_PATTERNS), array_values(self::MARKDOWN_PATTERNS), $text);
        $text = Str::squish(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));
        return $this->truncateToLimit($text, $limit);
    }

    private function safeStripTags(?string $text): ?string
    {
        return $text ? strip_tags($text) : null;
    }

    private function truncateToLimit(string $text, int $limit): string
    {
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
        $blogUrl = $this->buildBlogUrl($baseUrl, $blog->slug);
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => $blog->getSeoTitleWithFallback(),
            'url' => $blogUrl,
            'description' => $description,
            'author' => $this->createAuthorOrganization($blog->name),
            'blogPost' => $this->mapPostsToStructuredData($posts, $baseUrl, $blog->slug),
            'breadcrumb' => $this->generateBreadcrumbStructuredData($baseUrl, $blogUrl, $blog->name),
        ];
    }

    private function buildBlogUrl(string $baseUrl, string $slug): string
    {
        return $baseUrl . '/' . $slug;
    }

    private function createAuthorOrganization(string $name): array
    {
        return [
            '@type' => 'Organization',
            'name' => $name,
        ];
    }

    private function mapPostsToStructuredData(array $posts, string $baseUrl, string $blogSlug): array
    {
        return collect($posts)->map(function ($post) use ($baseUrl, $blogSlug) {
            $postUrl = $this->buildPostUrl($baseUrl, $blogSlug, $post->slug);
            return [
                '@type' => 'BlogPosting',
                'headline' => $post->getSeoTitleWithFallback(),
                'url' => $postUrl,
                'datePublished' => $this->getIsoDate($post->published_at),
                'abstract' => $this->safeStripTags($post->excerpt),
            ];
        })->all();
    }

    private function buildPostUrl(string $baseUrl, string $blogSlug, string $postSlug): string
    {
        return $this->buildBlogUrl($baseUrl, $blogSlug) . '/' . $postSlug;
    }

    private function getIsoDate(?DateTimeInterface $date): ?string
    {
        if ($date instanceof DateTimeInterface) {
            return method_exists($date, 'toIso8601String')
                ? $date->toIso8601String()
                : $date->format(DATE_ATOM); // Fallback for classes without `toIso8601String`
        }

        return null;
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
        $blogUrl = $this->buildBlogUrl($baseUrl, $blog->slug);
        $postUrl = $this->buildPostUrl($baseUrl, $blog->slug, $post->slug);
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->getSeoTitleWithFallback(),
            'description' => $description,
            'url' => $postUrl,
            'datePublished' => $this->getIsoDate($post->published_at),
            'dateModified' => $this->getIsoDate($post->updated_at),
            'author' => $this->createAuthorOrganization($blog->name),
            'publisher' => $this->createAuthorOrganization($blog->name),
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

    public function generateHomeStructuredData(array $blogs, string $title, string $description, string $baseUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => $title,
            'url' => $baseUrl,
            'description' => $description,
            'blogPost' => collect($blogs)->slice(0, 10)->map(function ($blog) use ($baseUrl) {
                return [
                    '@type' => 'BlogPosting',
                    'headline' => $blog['name'],
                    'author' => [
                        '@type' => 'Person',
                        'name' => $blog['author'],
                    ],
                    'url' => $baseUrl . '/' . $blog['slug'],
                    'abstract' => $this->safeStripTags($blog['descriptionHtml'] ?? ''),
                ];
            })->all(),
        ];
    }
}
