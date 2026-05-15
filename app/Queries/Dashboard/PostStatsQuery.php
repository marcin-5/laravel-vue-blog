<?php

namespace App\Queries\Dashboard;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;

class PostStatsQuery
{
    /**
     * Get statistics for the user's posts.
     *
     * @param User $user
     * @return array
     */
    public function handle(User $user): array
    {
        $blogIds = $user->blogs()->pluck('id');

        if ($blogIds->isEmpty()) {
            return [
                'timeline' => collect(),
                'performance' => collect(),
            ];
        }

        $now = now();
        $posts = Post::query()
            ->whereIn('blog_id', $blogIds)
            ->select(['id', 'blog_id', 'title', 'published_at', 'created_at'])
            ->withCount('pageViews as total_views')
            ->withCount(['pageViews as year_views' => fn($q) => $q->where('created_at', '>=', $now->copy()->subYear())])
            ->withCount(['pageViews as half_year_views' => fn($q) => $q->where('created_at', '>=', $now->copy()->subMonths(6))])
            ->withCount(['pageViews as month_views' => fn($q) => $q->where('created_at', '>=', $now->copy()->subMonth())])
            ->withCount(['pageViews as week_views' => fn($q) => $q->where('created_at', '>=', $now->copy()->subWeek())])
            ->withCount(['pageViews as day_views' => fn($q) => $q->where('created_at', '>=', $now->copy()->subDay())])
            ->get();

        return [
            'timeline' => $this->buildTimeline($posts),
            'performance' => $this->buildPerformance($posts),
        ];
    }

    private function buildTimeline(Collection $posts): Collection
    {
        return $posts->map(fn(Post $post) => [
            'id' => $post->id,
            'title' => $post->title,
            'published_at' => $post->published_at?->toIso8601String()
                ?? $post->created_at->toIso8601String(),
            'views' => [
                'total' => $post->total_views,
                'year' => $post->year_views,
                'half_year' => $post->half_year_views,
                'month' => $post->month_views,
                'week' => $post->week_views,
                'day' => $post->day_views,
            ],
        ])->sortByDesc('published_at')->values();
    }

    private function buildPerformance(Collection $posts): Collection
    {
        return $posts->map(function (Post $post) {
            $publishedAt = $post->published_at ?? $post->created_at;
            $daysSincePublished = (int) max(1, abs(now()->diffInDays($publishedAt)));

            return [
                'id' => $post->id,
                'title' => $post->title,
                'ratio' => round($post->total_views / $daysSincePublished, 2),
            ];
        })->sortByDesc('ratio')->values();
    }
}
