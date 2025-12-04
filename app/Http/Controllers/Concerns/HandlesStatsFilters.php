<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\StatsRange;
use App\Enums\StatsSort;
use App\Services\StatsCriteria;
use Illuminate\Http\Request;

trait HandlesStatsFilters
{
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

        return [
            'range' => $range,
            'sort' => $sort,
            'size' => $size,
            'limit' => $limit,
            'blogger_id' => $bloggerId,
            'blog_id' => $blogId,
        ];
    }

    protected function getPostViews(
        string $range,
        ?int $limit,
        string $sort,
        ?int $bloggerId = null,
        ?int $blogId = null,
    ) {
        $criteria = new StatsCriteria(
            range: StatsRange::from($range),
            bloggerId: $bloggerId,
            blogId: $blogId,
            limit: $limit,
            sort: StatsSort::from($sort),
        );

        return $this->stats->postViews($criteria);
    }

    protected function formatFiltersForResponse(array $filters, ?int $limit): array
    {
        return [
            'range' => $filters['range'],
            'sort' => $filters['sort'],
            'size' => $filters['size'] === 0 ? 0 : ($limit ?? 5),
            'blogger_id' => $filters['blogger_id'],
            'blog_id' => $filters['blog_id'],
        ];
    }
}
