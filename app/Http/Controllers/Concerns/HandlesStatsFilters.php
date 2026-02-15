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

        // First subsection: strictly from page_views table, ignore selected visitor_type
        $visitorsFromPageViews = $this->stats->visitorViews(
            new StatsCriteria(
                range: $visitorCriteria->range,
                bloggerId: $visitorCriteria->bloggerId,
                blogId: $visitorCriteria->blogId,
                limit: $visitorCriteria->limit,
                sort: $visitorCriteria->sort,
                visitorGroupBy: $visitorCriteria->visitorGroupBy,
                visitorType: 'all',
            ),
        );

        // Second subsection: from anonymous_views and/or bot_views based on selected type
        if ($visitorCriteria->visitorType === 'bots' || $visitorCriteria->visitorType === 'anonymous') {
            $visitorsFromSpecial = $this->stats->visitorViews($visitorCriteria);
        } else {
            // 'all' selected -> merge anonymous and bots
            $anonymous = $this->stats->visitorViews(
                new StatsCriteria(
                    range: $visitorCriteria->range,
                    bloggerId: $visitorCriteria->bloggerId,
                    blogId: $visitorCriteria->blogId,
                    limit: null, // merge then apply limit manually
                    sort: $visitorCriteria->sort,
                    visitorGroupBy: $visitorCriteria->visitorGroupBy,
                    visitorType: 'anonymous',
                ),
            );
            $bots = $this->stats->visitorViews(
                new StatsCriteria(
                    range: $visitorCriteria->range,
                    bloggerId: $visitorCriteria->bloggerId,
                    blogId: $visitorCriteria->blogId,
                    limit: null,
                    sort: $visitorCriteria->sort,
                    visitorGroupBy: $visitorCriteria->visitorGroupBy,
                    visitorType: 'bots',
                ),
            );

            $merged = collect();
            foreach ([$anonymous, $bots] as $set) {
                foreach ($set as $row) {
                    $key = $row['visitor_label'];
                    if (!$merged->has($key)) {
                        $merged->put($key, $row);
                    } else {
                        $existing = $merged->get($key);
                        $existing['blog_views'] += $row['blog_views'];
                        $existing['post_views'] += $row['post_views'];
                        $existing['views'] += $row['views'];
                        $existing['lifetime_views'] += $row['lifetime_views'];
                        $existing['user_agent'] = $existing['user_agent'] ?? $row['user_agent'];
                        $merged->put($key, $existing);
                    }
                }
            }

            // Apply sorting & limiting to merged collection similar to query
            $visitorsFromSpecial = $merged->values();
            switch ($visitorCriteria->sort) {
                case StatsSort::ViewsAsc:
                    $visitorsFromSpecial = $visitorsFromSpecial->sortBy('post_views')->values();
                    break;
                case StatsSort::NameAsc:
                    $visitorsFromSpecial = $visitorsFromSpecial->sortBy(
                        'visitor_label',
                        SORT_NATURAL | SORT_FLAG_CASE,
                    )->values();
                    break;
                case StatsSort::NameDesc:
                    $visitorsFromSpecial = $visitorsFromSpecial->sortByDesc(
                        'visitor_label',
                        SORT_NATURAL | SORT_FLAG_CASE,
                    )->values();
                    break;
                default:
                    $visitorsFromSpecial = $visitorsFromSpecial->sortByDesc('post_views')->values();
            }
            if ($visitorCriteria->limit !== null) {
                $visitorsFromSpecial = $visitorsFromSpecial->take(max(1, $visitorCriteria->limit))->values();
            }
        }

        return [
            'blogFilters' => $this->formatFiltersForResponse($blogFilters, $blogFilters['limit']),
            'postFilters' => $this->formatFiltersForResponse($postFilters, $postFilters['limit']),
            'visitorFilters' => $this->formatFiltersForResponse($visitorFilters, $visitorFilters['limit']),
            'blogs' => $blogs,
            'posts' => $posts,
            // Two separate datasets for Visitor sections
            'visitorsFromPage' => $visitorsFromPageViews,
            'visitorsFromSpecial' => $visitorsFromSpecial,
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
        $visitorType = (string)$request->query($prefix . 'type', 'all');

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
