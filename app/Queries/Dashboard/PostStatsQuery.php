<?php

declare(strict_types=1);

namespace App\Queries\Dashboard;

use App\Builders\PageViewBuilder;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;

class PostStatsQuery
{
    /**
     * Get statistics for the user's posts.
     *
     * @param User $user
     * @return array{
     *   timeline: Collection<int, array{id: int, title: string, published_at: string, views: array{total: int, year: int, half_year: int, month: int, week: int, day: int}}>,
     *   performance: Collection<int, array{id: int, title: string, ratio: float}>
     * }
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
            ->withCount(['pageViews as year_views' => fn(PageViewBuilder $q) => $q->where('created_at', '>=', $now->copy()->subYear())])
            ->withCount(['pageViews as half_year_views' => fn(PageViewBuilder $q) => $q->where('created_at', '>=', $now->copy()->subMonths(6))])
            ->withCount(['pageViews as month_views' => fn(PageViewBuilder $q) => $q->where('created_at', '>=', $now->copy()->subMonth())])
            ->withCount(['pageViews as week_views' => fn(PageViewBuilder $q) => $q->where('created_at', '>=', $now->copy()->subWeek())])
            ->withCount(['pageViews as day_views' => fn(PageViewBuilder $q) => $q->where('created_at', '>=', $now->copy()->subDay())])
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
                'total' => (int) $post->getAttribute('total_views'),
                'year' => (int) $post->getAttribute('year_views'),
                'half_year' => (int) $post->getAttribute('half_year_views'),
                'month' => (int) $post->getAttribute('month_views'),
                'week' => (int) $post->getAttribute('week_views'),
                'day' => (int) $post->getAttribute('day_views'),
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
                'ratio' => round(((int) $post->getAttribute('total_views')) / $daysSincePublished, 2),
            ];
        })->sortByDesc('ratio')->values();
    }
}
