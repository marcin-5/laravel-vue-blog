<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait FormatsPaginator
{
    /**
     * Format Laravel LengthAwarePaginator into a simple structure for the front-end.
     */
    protected function formatPagination(?LengthAwarePaginator $paginator): array
    {
        if (!$paginator) {
            return [];
        }

        $links = $paginator->linkCollection()->toArray();

        return [
            'links' => array_map(static function ($lnk) {
                return [
                    'url' => $lnk['url'] ?? null,
                    'label' => $lnk['label'] ?? '',
                    'active' => (bool)($lnk['active'] ?? false),
                ];
            }, $links),
            'prevUrl' => $paginator->previousPageUrl(),
            'nextUrl' => $paginator->nextPageUrl(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }
}
