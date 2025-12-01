<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait HandlesStatsFilters
{
    protected function parseStatsFilters(Request $request): array
    {
        $range = (string)$request->query('range', 'week');
        $sort = (string)$request->query('sort', 'views_desc');
        $size = $request->integer('size');
        // 0 means "All" -> no limit
        $limit = $size === 0 ? null : (in_array($size, [5, 10, 20], true) ? $size : 5);

        return [
            'range' => $range,
            'sort' => $sort,
            'size' => $size,
            'limit' => $limit,
        ];
    }

    protected function getPostViews(int $blogId, string $range, ?int $limit, string $sort): array
    {
        return $this->stats->postViews($range, $blogId, $limit, $sort);
    }

    protected function formatFiltersForResponse(array $filters, ?int $limit): array
    {
        return [
            'range' => $filters['range'],
            'sort' => $filters['sort'],
            'size' => $filters['size'] === 0 ? 0 : ($limit ?? 5),
        ];
    }
}
