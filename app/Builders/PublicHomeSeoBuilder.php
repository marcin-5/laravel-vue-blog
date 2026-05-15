<?php

namespace App\Builders;

use App\DataTransferObjects\SeoData;
use App\Services\SeoService;
use Illuminate\Support\Collection;

readonly class PublicHomeSeoBuilder
{
    public function __construct(
        private SeoService $seoService,
    ) {}

    /**
     * Build SEO data for the welcome page.
     */
    public function buildWelcomeSeo(Collection $blogs, array $messages, array $selectedCategoryIds): SeoData
    {
        $baseUrl = config('app.url');
        $locale = app()->getLocale();

        $title = data_get($messages, 'meta.welcomeTitle') ?? config('app.name');
        $description = data_get($messages, 'meta.welcomeDescription') ?? ('Welcome to ' . config('app.name'));

        $canonicalUrl = $baseUrl . (empty($selectedCategoryIds) ? '' : '?categories=' . implode(',', $selectedCategoryIds));

        $alternateLinks = [
            ['hreflang' => 'pl', 'href' => $canonicalUrl],
            ['hreflang' => 'en', 'href' => $canonicalUrl],
            ['hreflang' => 'x-default', 'href' => $canonicalUrl],
        ];

        return new SeoData(
            title: $title,
            description: $description,
            canonicalUrl: $canonicalUrl,
            ogImage: $this->getOgImage($baseUrl, $locale),
            ogType: 'website',
            locale: $locale,
            structuredData: $this->seoService->generateHomeStructuredData(
                $blogs->toArray(),
                $title,
                $description,
                $baseUrl,
            ),
            alternateLinks: $alternateLinks,
        );
    }

    /**
     * Build SEO data for the about page.
     */
    public function buildAboutSeo(array $messages): SeoData
    {
        $baseUrl = config('app.url');
        $locale = app()->getLocale();
        $canonicalUrl = rtrim($baseUrl, '/') . '/about';

        $title = data_get($messages, 'about.meta.title') ?? 'About';
        $description = data_get($messages, 'about.meta.description') ?? 'About this site';

        return new SeoData(
            title: $title,
            description: $description,
            canonicalUrl: $canonicalUrl,
            ogImage: $this->getOgImage($baseUrl, $locale),
            ogType: 'website',
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

    /**
     * Build SEO data for the contact page.
     */
    public function buildContactSeo(array $messages): SeoData
    {
        $baseUrl = config('app.url');
        $locale = app()->getLocale();
        $canonicalUrl = rtrim($baseUrl, '/') . '/contact';

        $title = data_get($messages, 'contact.meta.title') ?? 'Contact';
        $description = data_get($messages, 'contact.meta.description') ?? 'Get in touch';

        return new SeoData(
            title: $title,
            description: $description,
            canonicalUrl: $canonicalUrl,
            ogImage: $this->getOgImage($baseUrl, $locale),
            ogType: 'website',
            locale: $locale,
            structuredData: [
                '@context' => 'https://schema.org',
                '@type' => 'ContactPage',
                'name' => $title,
                'url' => $canonicalUrl,
                'description' => $description,
            ],
        );
    }

    private function getOgImage(string $baseUrl, string $locale): string
    {
        return rtrim($baseUrl, '/') . '/' . ($locale === 'pl' ? 'pl' : 'en') . '/og-image.png';
    }
}
