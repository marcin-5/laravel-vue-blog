<?php

namespace App\Queries\Dashboard;

use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;

class BlogStatsQuery
{
    /**
     * Get statistics for the user's blogs.
     *
     * @param User $user
     * @return Collection
     */
    public function handle(User $user): Collection
    {
        $blogs = Blog::query()
            ->where('user_id', $user->id)
            ->select(['id', 'name', 'user_id'])
            ->withCount('posts')
            ->withCount([
                'newsletterSubscriptions as daily_subscriptions_count' => fn($query) => $query->where(
                    'frequency',
                    'daily',
                ),
                'newsletterSubscriptions as weekly_subscriptions_count' => fn($query) => $query->where(
                    'frequency',
                    'weekly',
                ),
            ])
            ->get();

        if ($blogs->isEmpty()) {
            return collect();
        }

        $viewCounts = $this->getBlogViewCounts($blogs);

        return $blogs->map(fn(Blog $blog) => [
            'id' => $blog->id,
            'name' => $blog->name,
            'posts_count' => $blog->posts_count,
            'lifetime_views' => $viewCounts[$blog->id] ?? 0,
            'daily_subscriptions_count' => $blog->daily_subscriptions_count,
            'weekly_subscriptions_count' => $blog->weekly_subscriptions_count,
        ]);
    }

    /**
     * Get total view counts for a collection of blogs using a single query.
     */
    private function getBlogViewCounts(Collection $blogs): Collection
    {
        $postMorphClass = (new Post)->getMorphClass();

        return PageView::query()
            ->selectRaw('posts.blog_id, count(*) as count')
            ->join('posts', 'page_views.viewable_id', '=', 'posts.id')
            ->where('page_views.viewable_type', $postMorphClass)
            ->whereIn('posts.blog_id', $blogs->pluck('id'))
            ->groupBy('posts.blog_id')
            ->pluck('count', 'blog_id');
    }
}
