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

        // Add home page
        $sitemap->add(
            Url::create(route('home'))
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0),
        );

        // Add published blogs
        Blog::where('is_published', true)
            ->get()
            ->each(function (Blog $blog) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('blog.public.landing', $blog->slug))
                        ->setLastModificationDate($blog->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.8),
                );
            });

        // Add published posts
        Post::with('blog')
            ->published()
            ->public()
            ->whereHas('blog', function ($query) {
                $query->where('is_published', true);
            })
            ->get()
            ->each(function (Post $post) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('blog.public.post', [$post->blog->slug, $post->slug]))
                        ->setLastModificationDate($post->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.6),
                );
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
