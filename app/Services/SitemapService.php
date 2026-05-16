<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Blog;
use App\Models\Post;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url as SitemapUrl;

readonly class SitemapService
{
    public function getSitemap(string $locale): string
    {
        // Ensure links in sitemap use the correct domain for the locale
        $domain = $this->getDomainForLocale($locale);
        $originalRootUrl = config('app.url');

        if ($domain) {
            $scheme = parse_url($originalRootUrl, PHP_URL_SCHEME) ?: 'https';
            // `forceRootUrl` is deprecated. Temporarily override the application's URL
            // so generated routes use the correct host for the given locale.
            config()->set('app.url', $scheme . '://' . $domain);
        }

        $sitemap = Sitemap::create();

        $this->addHomepage($sitemap);

        $this->addPublishedBlogs($sitemap, $locale);

        $this->addPublishedPosts($sitemap, $locale);

        $xml = $sitemap->render();

        if ($domain) {
            // Restore the original application URL after generation
            config()->set('app.url', $originalRootUrl);
        }

        return $xml;
    }

    private function getDomainForLocale(string $locale): ?string
    {
        $domainLocales = config('app.domain_locales', []);
        $localesToDomains = array_flip($domainLocales);

        return $localesToDomains[$locale] ?? null;
    }

    private function addHomepage(Sitemap $sitemap): void
    {
        $sitemap->add(
            SitemapUrl::create(route('home'))
                ->setLastModificationDate(now())
                ->setChangeFrequency('daily')
                ->setPriority(1.0),
        );
    }

    private function addPublishedBlogs(Sitemap $sitemap, string $locale): void
    {
        Blog::published()
            ->forLocale($locale)
            ->cursor()
            ->each(function (Blog $blog) use ($sitemap): void {
                $sitemap->add(
                    SitemapUrl::create(route('blog.public.landing', $blog->slug))
                        ->setLastModificationDate($blog->updated_at)
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.8),
                );
            });
    }

    private function addPublishedPosts(Sitemap $sitemap, string $locale): void
    {
        Post::with('blog')
            ->published()
            ->public()
            ->whereHas('blog', function ($query) use ($locale): void {
                $query
                    ->where('is_published', true)
                    ->where('locale', $locale);
            })
            ->cursor()
            ->each(function (Post $post) use ($sitemap): void {
                $sitemap->add(
                    SitemapUrl::create(route('blog.public.post', [$post->blog->slug, $post->slug]))
                        ->setLastModificationDate($post->updated_at)
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.6),
                );
            });
    }
}
