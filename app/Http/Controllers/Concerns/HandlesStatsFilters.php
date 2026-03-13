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
        $postFilters = $this->parseStatsFilters($request, 'posts_');
        $visitorFilters = $this->parseStatsFilters($request, 'visitors_');
        $specialVisitorFilters = $this->parseStatsFilters($request, 'special_visitors_');

        // Force lifetime range for Anonymous and Bot Views regardless of incoming query, as the range selector is hidden in UI
        $specialVisitorFilters['range'] = 'lifetime';

        $blogCriteria = $this->createCriteria($blogFilters, $forceBloggerId);
        $postCriteria = $this->createCriteria($postFilters, $forceBloggerId);
        $visitorCriteria = $this->createCriteria($visitorFilters, $forceBloggerId);
        $specialVisitorCriteria = $this->createCriteria($specialVisitorFilters, $forceBloggerId);

        return [
            'blogFilters' => $this->formatFiltersForResponse($blogFilters, $blogFilters['limit']),
            'postFilters' => $this->formatFiltersForResponse($postFilters, $postFilters['limit']),
            'visitorFilters' => $this->formatFiltersForResponse($visitorFilters, $visitorFilters['limit']),
            'specialVisitorFilters' => $this->formatFiltersForResponse(
                $specialVisitorFilters,
                $specialVisitorFilters['limit'],
            ),
            'blogs' => $this->stats->blogViews($blogCriteria),
            'posts' => $this->stats->postViews($postCriteria),
            // First subsection: strictly from page_views table, ignore selected visitor_type
            'visitorsFromPage' => $this->stats->visitorViews(
                new StatsCriteria(
                    range: $visitorCriteria->range,
                    bloggerId: $visitorCriteria->bloggerId,
                    blogId: $visitorCriteria->blogId,
                    limit: $visitorCriteria->limit,
                    sort: $visitorCriteria->sort,
                    visitorGroupBy: $visitorCriteria->visitorGroupBy,
                    visitorType: 'all',
                ),
            ),
            // Second subsection: from anonymous_views and/or bot_views based on selected type
            'visitorsFromSpecial' => $this->stats->specialVisitorViews($specialVisitorCriteria),
        ];
    }

    protected function parseStatsFilters(Request $request, string $prefix = ''): array
    {
        $range = (string) $request->query($prefix . 'range', 'week');
        $sort = (string) $request->query($prefix . 'sort', 'views_desc');
        // Default to 5 items when no explicit size is provided; 0 still means "All".
        $size = $request->integer($prefix . 'size', 5);
        // 0 means "All" -> no limit
        $limit = $size === 0 ? null : (in_array($size, [5, 10, 20], true) ? $size : 5);
        $bloggerId = $request->has($prefix . 'blogger_id') ? (int) $request->query($prefix . 'blogger_id') : null;
        $blogId = $request->has($prefix . 'blog_id') ? (int) $request->query($prefix . 'blog_id') : null;
        $groupBy = (string) $request->query($prefix . 'group_by', 'visitor_id');
        $visitorType = (string) $request->query($prefix . 'type', 'all');

        return [
            'range' => $range,
            'sort' => $sort,
            'size' => $size,
            'limit' => $limit,
            'blogger_id' => $bloggerId,
            'blog_id' => $blogId,
            'group_by' => $groupBy,
            'visitor_type' => $visitorType,
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
            visitorType: $filters['visitor_type'] ?? 'all',
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
            'visitor_type' => $filters['visitor_type'] ?? 'all',
        ];
    }
}
