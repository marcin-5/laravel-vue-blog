<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Blog;
use App\Models\Post;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url as SitemapUrl;

readonly class SitemapService
{
    public function getSitemap(string $locale, ?Blog $blog = null): string
    {
        // Ensure links in sitemap use the correct domain for the locale
        $originalRootUrl = config('app.url');

        if (!$blog) {
            $domain = $this->getDomainForLocale($locale);
            if ($domain) {
                $scheme = parse_url($originalRootUrl, PHP_URL_SCHEME) ?: 'https';
                config()->set('app.url', $scheme . '://' . $domain);
            }
        }

        $sitemap = Sitemap::create();

        if (!$blog) {
            $this->addHomepage($sitemap);
            $this->addSystemPages($sitemap);
            $this->addPublishedBlogs($sitemap, $locale);
        } else {
            $this->addBlogLanding($sitemap, $blog);
            $this->addBlogPosts($sitemap, $blog);
            $this->addBlogTags($sitemap, $blog);
        }

        $xml = $sitemap->render();

        // Restore the original application URL after generation
        config()->set('app.url', $originalRootUrl);

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

    private function addSystemPages(Sitemap $sitemap): void
    {
        foreach (['about', 'contact', 'newsletter.index'] as $routeName) {
            try {
                $sitemap->add(
                    SitemapUrl::create(route($routeName))
                        ->setLastModificationDate(now())
                        ->setChangeFrequency('monthly')
                        ->setPriority(0.7),
                );
            } catch (\Throwable) {
                // Skip routes that might not be defined
            }
        }
    }

    private function addPublishedBlogs(Sitemap $sitemap, string $locale): void
    {
        Blog::published()
            ->forLocale($locale)
            ->cursor()
            ->each(function (Blog $blog) use ($sitemap): void {
                $sitemap->add(
                    SitemapUrl::create($blog->public_url)
                        ->setLastModificationDate($blog->updated_at)
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.8),
                );
            });
    }

    private function addBlogLanding(Sitemap $sitemap, Blog $blog): void
    {
        $sitemap->add(
            SitemapUrl::create($blog->public_url)
                ->setLastModificationDate($blog->updated_at)
                ->setChangeFrequency('daily')
                ->setPriority(1.0),
        );
    }

    private function addBlogPosts(Sitemap $sitemap, Blog $blog): void
    {
        $blog
            ->posts()
            ->published()
            ->public()
            ->whereNull('group_id') // Explicitly exclude group posts
            ->cursor()
            ->each(function (Post $post) use ($sitemap): void {
                $sitemap->add(
                    SitemapUrl::create($post->public_url)
                        ->setLastModificationDate($post->updated_at)
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.8),
                );
            });
    }

    private function addBlogTags(Sitemap $sitemap, Blog $blog): void
    {
        $blog->tags->each(function ($tag) use ($sitemap, $blog): void {
            try {
                $url = route('blog.public.tag', [
                    'blog' => $blog->slug,
                    'tag' => $tag->slug,
                    'mainDomain' => $blog->main_domain,
                ]);

                $sitemap->add(
                    SitemapUrl::create($url)
                        ->setLastModificationDate($tag->updated_at ?? $blog->updated_at)
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.4),
                );
            } catch (\Throwable) {
                // Skip if route generation fails
            }
        });
    }
}
