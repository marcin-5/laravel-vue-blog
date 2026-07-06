<?php

namespace App\Observers;

use App\Jobs\IndexNowSubmitJob;
use App\Models\Blog;
use App\Models\IndexNowQueuedUrl;
use App\Models\Post;
use App\Services\IndexNowService;
use Illuminate\Support\Facades\Cache;

class IndexNowObserver
{
    public function __construct(protected IndexNowService $indexNowService) {}

    public function saved(Post|Blog $model): void
    {
        $url = $this->getUrl($model);
        if (!$url) {
            return;
        }

        $visibility = $this->getVisibility($model);
        $isPublished = (bool) ($model->is_published ?? false);

        $shouldSubmit = $this->indexNowService->shouldSubmit(
            $url,
            $visibility,
            $isPublished,
        );

        if ($shouldSubmit) {
            $relevantAttributes = $model instanceof Post
                ? ['title', 'seo_title', 'slug', 'excerpt', 'summary', 'content', 'is_published', 'visibility']
                : ['name', 'seo_title', 'slug', 'description', 'motto', 'footer', 'is_published', 'visibility'];

            if ($model->wasRecentlyCreated || $model->wasChanged($relevantAttributes)) {
                IndexNowQueuedUrl::updateOrCreate(['url' => $url]);

                if ($model->wasChanged('slug')) {
                    $oldUrl = $this->getOldUrl($model);
                    if ($oldUrl) {
                        IndexNowQueuedUrl::updateOrCreate(['url' => $oldUrl]);
                    }

                    if ($model instanceof Blog) {
                        $this->queuePostsForBlog($model);
                    }
                }

                $this->scheduleJob();
            }
        } else {
            IndexNowQueuedUrl::where('url', $url)->delete();
        }
    }

    protected function getUrl(Post|Blog $model): ?string
    {
        $blog = $model instanceof Post ? $model->blog : $model;
        if (!$blog) {
            return null;
        }

        if ($model instanceof Post) {
            return route('blog.public.post', [
                'blog' => $blog->slug,
                'postSlug' => $model->slug,
                'mainDomain' => $blog->main_domain,
            ]);
        }

        return $blog->public_url;
    }

    protected function getVisibility(Post|Blog $model): string
    {
        return $model->visibility ?? 'public';
    }

    protected function getOldUrl(Post|Blog $model): ?string
    {
        $oldSlug = $model->getOriginal('slug');
        if (!$oldSlug || $oldSlug === $model->slug) {
            return null;
        }

        $blog = $model instanceof Post ? $model->blog : $model;
        if (!$blog) {
            return null;
        }

        if ($model instanceof Post) {
            return route('blog.public.post', [
                'blog' => $blog->slug,
                'postSlug' => $oldSlug,
                'mainDomain' => $blog->main_domain,
            ]);
        }

        return route('blog.public.landing', [
            'blog' => $oldSlug,
            'mainDomain' => $blog->main_domain,
        ]);
    }

    protected function queuePostsForBlog(Blog $blog): void
    {
        $oldBlogSlug = $blog->getOriginal('slug');
        if (!$oldBlogSlug || $oldBlogSlug === $blog->slug) {
            return;
        }

        $mainDomain = $blog->main_domain;

        $blog->posts()->published()->public()->each(function (Post $post) use ($blog, $oldBlogSlug, $mainDomain) {
            $newUrl = route('blog.public.post', [
                'blog' => $blog->slug,
                'postSlug' => $post->slug,
                'mainDomain' => $mainDomain,
            ]);
            $oldUrl = route('blog.public.post', [
                'blog' => $oldBlogSlug,
                'postSlug' => $post->slug,
                'mainDomain' => $mainDomain,
            ]);

            IndexNowQueuedUrl::updateOrCreate(['url' => $newUrl]);
            IndexNowQueuedUrl::updateOrCreate(['url' => $oldUrl]);
        });
    }

    protected function scheduleJob(): void
    {
        // Require at least 1 hour delay from now for submission.
        // We dispatch the job with 1 hour delay.
        // If multiple updates happen, each will trigger a job, but they will check the queue.
        // To respect "set new submission time to one hour from now", we can use a lock or just dispatch.
        // Actually, if we want to ensure only ONE job runs after 1 hour from the LATEST update:
        // We can't easily cancel previously dispatched jobs in standard Laravel queue.
        // But we can check a timestamp in Cache.

        Cache::put('index_now_next_run', now()->addHour(), now()->addHour());
        IndexNowSubmitJob::dispatch()->delay(now()->addHour());
    }

    public function deleted(Post|Blog $model): void
    {
        $url = $this->getUrl($model);
        if ($url) {
            IndexNowQueuedUrl::where('url', $url)->delete();
        }
    }
}
