<?php

namespace App\Builders;

use App\DataTransferObjects\SeoData;
use App\Models\Blog;
use App\Models\Post;
use App\Services\SeoService;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class PublicBlogSeoBuilder
{
    public function __construct(
        private SeoService $seoService,
    ) {}

    /**
     * Build SEO data for the blog landing page.
     */
    public function buildLandingSeo(Blog $blog, LengthAwarePaginator $paginator, string $metaDescription): SeoData
    {
        $seoTitle = $blog->seo_title ?: ($blog->name . ' - ' . config('app.name'));
        $baseUrl = config('app.url');
        $locale = app()->getLocale();

        return new SeoData(
            title: $seoTitle,
            description: $metaDescription,
            canonicalUrl: $baseUrl . '/' . $blog->slug,
            ogImage: $baseUrl . '/' . ($locale === 'pl' ? 'pl' : 'en') . '/og-image.png',
            ogType: 'blog',
            locale: $locale,
            structuredData: $this->seoService->generateBlogStructuredData(
                $blog,
                $paginator->items(),
                $baseUrl,
                $metaDescription,
            ),
        );
    }

    /**
     * Build SEO data for a single post page.
     */
    public function buildPostSeo(Blog $blog, Post $post, string $metaDescription): SeoData
    {
        $seoTitle = $post->seo_title ?: ($post->title . ' - ' . $blog->name);
        $baseUrl = config('app.url');
        $locale = app()->getLocale();

        return new SeoData(
            title: $seoTitle,
            description: $metaDescription,
            canonicalUrl: $baseUrl . '/' . $blog->slug . '/' . $post->slug,
            ogImage: $baseUrl . '/' . ($locale === 'pl' ? 'pl' : 'en') . '/og-image.png',
            ogType: 'article',
            locale: $locale,
            structuredData: $this->seoService->generatePostStructuredData($blog, $post, $baseUrl, $metaDescription),
            publishedTime: $post->published_at?->toIso8601String(),
            modifiedTime: $post->updated_at?->toIso8601String(),
        );
    }
}
