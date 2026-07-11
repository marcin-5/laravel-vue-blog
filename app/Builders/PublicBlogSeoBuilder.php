<?php

namespace App\Builders;

use App\DataTransferObjects\SeoData;
use App\Models\Blog;
use App\Models\Post;
use App\Models\Tag;
use App\Services\SeoService;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class PublicBlogSeoBuilder
{
    private const string POLISH_LOCALE = 'pl';

    private const string DEFAULT_OG_LOCALE = 'en';

    private const string BLOG_OG_TYPE = 'blog';

    private const string ARTICLE_OG_TYPE = 'article';

    private const string OG_IMAGE_FILENAME = 'og-image.png';

    public function __construct(
        private SeoService $seoService,
    ) {}

    /**
     * Build SEO data for the blog landing page.
     */
    public function buildLandingSeo(
        Blog $blog,
        LengthAwarePaginator $paginator,
        string $metaDescription,
        ?Tag $tag = null,
    ): SeoData {
        $baseUrl = $this->getBlogBaseUrl($blog);
        $appBaseUrl = $this->getAppBaseUrl($blog);
        $locale = app()->getLocale();

        return new SeoData(
            title: $this->getLandingTitle($blog, $tag),
            description: $metaDescription,
            canonicalUrl: $this->getLandingCanonicalUrl($baseUrl, $tag),
            ogImage: $this->getOgImage($appBaseUrl, $locale),
            ogType: self::BLOG_OG_TYPE,
            locale: $locale,
            structuredData: $this->seoService->generateBlogStructuredData(
                $blog,
                $paginator->items(),
                $baseUrl,
                $metaDescription,
            ),
        );
    }

    private function getBlogBaseUrl(Blog $blog): string
    {
        return $this->getScheme() . $blog->slug . '.' . $this->getMainDomain($blog);
    }

    private function getScheme(): string
    {
        return request()->isSecure() ? 'https://' : 'http://';
    }

    private function getMainDomain(Blog $blog): string
    {
        return $blog->locale === self::POLISH_LOCALE
            ? config('app.domain')
            : config('app.domain_secondary');
    }

    private function getAppBaseUrl(Blog $blog): string
    {
        return $this->getScheme() . $this->getMainDomain($blog);
    }

    private function getLandingTitle(Blog $blog, ?Tag $tag): string
    {
        if ($tag !== null) {
            return $tag->name . ' - ' . $blog->name;
        }

        return $blog->seo_title ?: ($blog->name . ' - ' . config('app.name'));
    }

    private function getLandingCanonicalUrl(string $baseUrl, ?Tag $tag): string
    {
        if ($tag === null) {
            return $baseUrl;
        }

        return $baseUrl . '/tags/' . $tag->slug;
    }

    private function getOgImage(string $baseUrl, string $locale): string
    {
        $imageLocale = $locale === self::POLISH_LOCALE
            ? self::POLISH_LOCALE
            : self::DEFAULT_OG_LOCALE;

        return $baseUrl . '/' . $imageLocale . '/' . self::OG_IMAGE_FILENAME;
    }

    /**
     * Build SEO data for a single post page.
     */
    public function buildPostSeo(Blog $blog, Post $post, string $metaDescription): SeoData
    {
        $baseUrl = $this->getBlogBaseUrl($blog);
        $appBaseUrl = $this->getAppBaseUrl($blog);
        $locale = app()->getLocale();

        return new SeoData(
            title: $this->getPostTitle($blog, $post),
            description: $metaDescription,
            canonicalUrl: $baseUrl . '/' . $post->slug,
            ogImage: $this->getOgImage($appBaseUrl, $locale),
            ogType: self::ARTICLE_OG_TYPE,
            locale: $locale,
            structuredData: $this->seoService->generatePostStructuredData($blog, $post, $baseUrl, $metaDescription),
            publishedTime: $post->published_at?->toIso8601String(),
            modifiedTime: $post->updated_at?->toIso8601String(),
        );
    }

    private function getPostTitle(Blog $blog, Post $post): string
    {
        return $post->seo_title ?: ($post->title . ' - ' . $blog->name);
    }

    /**
     * Build SEO data for the blog about page.
     */
    public function buildAboutSeo(Blog $blog): SeoData
    {
        $baseUrl = $this->getBlogBaseUrl($blog);
        $appBaseUrl = $this->getAppBaseUrl($blog);
        $locale = app()->getLocale();
        $canonicalUrl = $baseUrl . '/about';

        $title = ($locale === self::POLISH_LOCALE ? 'O nas' : 'About') . ' - ' . $blog->name;
        $description = $blog->seo_description ?: ($this->seoService->generateMetaDescription($blog->about) ?: $blog->name);

        return new SeoData(
            title: $title,
            description: $description,
            canonicalUrl: $canonicalUrl,
            ogImage: $this->getOgImage($appBaseUrl, $locale),
            ogType: self::BLOG_OG_TYPE,
            locale: $locale,
            structuredData: [
                '@context' => 'https://schema.org',
                '@type' => 'AboutPage',
                'name' => $title,
                'url' => $canonicalUrl,
                'description' => $description,
            ],
        );
    }
}
