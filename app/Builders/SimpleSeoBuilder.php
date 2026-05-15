<?php

namespace App\Builders;

use App\DataTransferObjects\SeoData;

readonly class SimpleSeoBuilder
{
    /**
     * Build simple SEO data for internal or less critical pages.
     */
    public function build(string $title, ?string $description = null, bool $noindex = true): SeoData
    {
        $baseUrl = config('app.url');
        $locale = app()->getLocale();
        $canonicalUrl = request()->url();

        return new SeoData(
            title: $title,
            description: $description ?? $title,
            canonicalUrl: $canonicalUrl,
            ogImage: rtrim($baseUrl, '/') . '/' . ($locale === 'pl' ? 'pl' : 'en') . '/og-image.png',
            ogType: 'website',
            locale: $locale,
            structuredData: [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'url' => $canonicalUrl,
            ],
            robots: $noindex ? 'noindex, nofollow' : 'index, follow',
        );
    }
}
