<?php

declare(strict_types=1);

namespace App\Queries\Stats;

use App\Models\BotView;
use App\DataTransferObjects\Stats\StatsCriteria;
use Illuminate\Support\Collection;

class BotViewsQuery
{
    /**
     * @return Collection<int, array{user_agent:string,hits:int,last_seen_at:string}>
     */
    public function handle(StatsCriteria $criteria): Collection
    {
        return BotView::query()
            ->with('userAgent')
            ->forMorphType($criteria->morphClass)
            ->when($criteria->viewableId, fn($q) => $q->where('viewable_id', $criteria->viewableId))
            ->topByHits($criteria->limit)
            ->get()
            ->map(fn(BotView $botView) => [
                'user_agent' => $botView->userAgent->name,
                'hits' => $botView->hits,
                'last_seen_at' => $botView->last_seen_at->toIso8601String(),
            ]);
    }
}
