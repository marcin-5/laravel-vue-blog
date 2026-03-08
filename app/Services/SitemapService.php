<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Post;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapService
{
    public function generate(): void
    {
        $sitemap = Sitemap::create();

        $this->addHomepage($sitemap);

        $this->addPublishedBlogs($sitemap);

        $this->addPublishedPosts($sitemap);

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }

    private function addHomepage(Sitemap $sitemap): void
    {
        $sitemap->add(
            Url::create(route('home'))
                ->setLastModificationDate(now())
                ->setChangeFrequency('daily')
                ->setPriority(1.0),
        );
    }

    private function addPublishedBlogs(Sitemap $sitemap): void
    {
        Blog::where('is_published', true)
            ->cursor()
            ->each(function (Blog $blog) use ($sitemap): void {
                $sitemap->add(
                    Url::create(route('blog.public.landing', $blog->slug))
                        ->setLastModificationDate($blog->updated_at)
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.8),
                );
            });
    }

    private function addPublishedPosts(Sitemap $sitemap): void
    {
        Post::with('blog')
            ->published()
            ->public()
            ->whereHas('blog', function ($query): void {
                $query->where('is_published', true);
            })
            ->cursor()
            ->each(function (Post $post) use ($sitemap): void {
                $sitemap->add(
                    Url::create(route('blog.public.post', [$post->blog->slug, $post->slug]))
                        ->setLastModificationDate($post->updated_at)
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.6),
                );
            });
    }
}
