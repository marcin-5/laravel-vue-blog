<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\StatsRange;
use App\Enums\StatsSort;
use App\Models\Blog;
use App\Services\StatsCriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait HandlesStatsFilters
{
    protected function getBlogOptions(?int $bloggerId = null): Collection
    {
        return Blog::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->when($bloggerId, fn($q) => $q->where('user_id', $bloggerId))
            ->get();
    }

    protected function getStatsData(Request $request, ?int $forceBloggerId = null): array
    {
        $blogFilters = $this->parseStatsFilters($request);
        $blogCriteria = $this->createCriteria($blogFilters, $forceBloggerId);
        $blogs = $this->stats->blogViews($blogCriteria);

        $postFilters = $this->parseStatsFilters($request, 'posts_');
        $postCriteria = $this->createCriteria($postFilters, $forceBloggerId);
        $posts = $this->stats->postViews($postCriteria);

        $visitorFilters = $this->parseStatsFilters($request, 'visitors_');
        $visitorCriteria = $this->createCriteria($visitorFilters, $forceBloggerId);
        $visitors = $this->stats->visitorViews($visitorCriteria);

        return [
            'blogFilters' => $this->formatFiltersForResponse($blogFilters, $blogFilters['limit']),
            'postFilters' => $this->formatFiltersForResponse($postFilters, $postFilters['limit']),
            'visitorFilters' => $this->formatFiltersForResponse($visitorFilters, $visitorFilters['limit']),
            'blogs' => $blogs,
            'posts' => $posts,
            'visitors' => $visitors,
        ];
    }

    protected function parseStatsFilters(Request $request, string $prefix = ''): array
    {
        $range = (string)$request->query($prefix . 'range', 'week');
        $sort = (string)$request->query($prefix . 'sort', 'views_desc');
        // Default to 5 items when no explicit size is provided; 0 still means "All".
        $size = $request->integer($prefix . 'size', 5);
        // 0 means "All" -> no limit
        $limit = $size === 0 ? null : (in_array($size, [5, 10, 20], true) ? $size : 5);
        $bloggerId = $request->has($prefix . 'blogger_id') ? (int)$request->query($prefix . 'blogger_id') : null;
        $blogId = $request->has($prefix . 'blog_id') ? (int)$request->query($prefix . 'blog_id') : null;
        $groupBy = (string)$request->query($prefix . 'group_by', 'visitor_id');

        return [
            'range' => $range,
            'sort' => $sort,
            'size' => $size,
            'limit' => $limit,
            'blogger_id' => $bloggerId,
            'blog_id' => $blogId,
            'group_by' => $groupBy,
        ];
    }

    protected function createCriteria(array $filters, ?int $forceBloggerId = null): StatsCriteria
    {
        return new StatsCriteria(
            range: StatsRange::from($filters['range']),
            bloggerId: $forceBloggerId ?? $filters['blogger_id'],
            blogId: $filters['blog_id'],
            limit: $filters['limit'],
            sort: StatsSort::from($filters['sort']),
            visitorGroupBy: $filters['group_by'] ?? 'visitor_id',
        );
    }

    protected function formatFiltersForResponse(array $filters, ?int $limit): array
    {
        return [
            'range' => $filters['range'],
            'sort' => $filters['sort'],
            'size' => $filters['size'] === 0 ? 0 : ($limit ?? 5),
            'blogger_id' => $filters['blogger_id'],
            'blog_id' => $filters['blog_id'],
            'group_by' => $filters['group_by'] ?? 'visitor_id',
        ];
    }
}
