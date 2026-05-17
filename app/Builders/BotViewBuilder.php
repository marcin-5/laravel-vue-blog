<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\BotView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @template TModelClass of BotView
 * @extends Builder<TModelClass>
 */
class BotViewBuilder extends Builder
{
    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);
    }

    /**
     * Scope: Filter by morphable type.
     */
    public function forMorphType(string $morphClass): self
    {
        return $this->where('viewable_type', $morphClass);
    }

    /**
     * Scope: Order by hits and limit.
     */
    public function topByHits(int $limit = 5): self
    {
        return $this->orderByDesc('hits')->limit($limit);
    }

    /**
     * Scope: Aggregate by user agent.
     */
    public function aggregatedByUserAgent(): self
    {
        return $this
            ->selectRaw('user_agent_id, SUM(hits) as total_hits, MAX(last_seen_at) as last_seen_at')
            ->groupBy('user_agent_id');
    }
}
