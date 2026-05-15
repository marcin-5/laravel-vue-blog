<?php

namespace App\Builders;

use App\DataTransferObjects\SeoData;
use App\Models\Group;
use App\Models\Post;

readonly class GroupSeoBuilder
{
    /**
     * Build SEO data for the group landing page.
     */
    public function buildLandingSeo(Group $group): SeoData
    {
        $baseUrl = config('app.url');
        $locale = app()->getLocale();

        return new SeoData(
            title: $group->name . ' - ' . config('app.name'),
            description: strip_tags($group->content_html ?? ''),
            canonicalUrl: $baseUrl . '/' . $group->slug,
            ogImage: $baseUrl . '/' . ($locale === 'pl' ? 'pl' : 'en') . '/og-image.png',
            ogType: 'website',
            locale: $locale,
            structuredData: [],
        );
    }

    /**
     * Build SEO data for a group post page.
     */
    public function buildPostSeo(Group $group, Post $post): SeoData
    {
        $baseUrl = config('app.url');
        $locale = app()->getLocale();

        return new SeoData(
            title: $post->title . ' - ' . $group->name,
            description: $post->excerpt ?: strip_tags($post->content_html ?? ''),
            canonicalUrl: $baseUrl . '/' . $group->slug . '/' . $post->slug,
            ogImage: $baseUrl . '/' . ($locale === 'pl' ? 'pl' : 'en') . '/og-image.png',
            ogType: 'article',
            locale: $locale,
            structuredData: [],
            publishedTime: $post->published_at?->toIso8601String(),
            modifiedTime: $post->updated_at?->toIso8601String(),
        );
    }
}
