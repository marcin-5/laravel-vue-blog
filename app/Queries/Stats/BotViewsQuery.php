<?php

namespace App\Queries\Stats;

use App\Models\BotView;
use App\Services\StatsCriteria;
use Illuminate\Support\Collection;

class BotViewsQuery
{
    /**
     * @return Collection<int, array{user_agent:string,hits:int,last_seen_at:string}>
     */
    public function execute(StatsCriteria $criteria): Collection
    {
        return BotView::query()
            ->with('userAgent')
            ->where('viewable_type', $criteria->morphClass)
            ->when($criteria->viewableId, fn($q) => $q->where('viewable_id', $criteria->viewableId))
            ->orderBy('hits', 'desc')
            ->limit($criteria->limit)
            ->get()
            ->map(fn(BotView $botView) => [
                'user_agent' => $botView->userAgent->name,
                'hits' => $botView->hits,
                'last_seen_at' => $botView->last_seen_at->toIso8601String(),
            ]);
    }
}
