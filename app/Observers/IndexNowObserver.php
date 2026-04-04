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

        $shouldSubmit = $this->indexNowService->shouldSubmit(
            $url,
            $this->getVisibility($model),
            (bool) ($model->is_published ?? false),
        );

        if ($shouldSubmit) {
            IndexNowQueuedUrl::updateOrCreate(['url' => $url]);
            $this->scheduleJob();
        } else {
            IndexNowQueuedUrl::where('url', $url)->delete();
        }
    }

    protected function getUrl(Post|Blog $model): ?string
    {
        if ($model instanceof Post && $model->blog) {
            return route('blog.public.post', [$model->blog->slug, $model->slug]);
        }

        if ($model instanceof Blog) {
            return route('blog.public.landing', $model->slug);
        }

        return null;
    }

    protected function getVisibility(Post|Blog $model): string
    {
        return $model->visibility ?? 'public';
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
