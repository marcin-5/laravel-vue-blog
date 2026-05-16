<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PostObserver
{
    public function created(Post $post): void
    {
        $this->clearNavigationCache($post);
    }

    private function clearNavigationCache(Post $post): void
    {
        if ($post->blog_id) {
            Cache::tags(["navigation:App\\Models\\Blog:$post->blog_id"])->flush();
        }

        if ($post->group_id) {
            Cache::tags(["navigation:App\\Models\\Group:$post->group_id"])->flush();
        }
    }

    public function updated(Post $post): void
    {
        // Only clear if relevant fields changed
        if ($post->isDirty(['title', 'slug', 'is_published', 'visibility', 'published_at', 'blog_id', 'group_id'])) {
            $this->clearNavigationCache($post);
        }
    }

    public function deleted(Post $post): void
    {
        $this->clearNavigationCache($post);
    }

    public function restored(Post $post): void
    {
        $this->clearNavigationCache($post);
    }
}
