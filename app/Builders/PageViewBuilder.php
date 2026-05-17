<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\PageView;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @template TModelClass of PageView
 * @extends Builder<TModelClass>
 */
class PageViewBuilder extends Builder
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
        return $this->where('page_views.viewable_type', $morphClass);
    }

    /**
     * Scope: Filter by date range.
     */
    public function withinDateRange(DateTimeInterface|array|null $fromOrBounds, ?DateTimeInterface $to = null): self
    {
        if (is_array($fromOrBounds)) {
            [$from, $to] = $fromOrBounds;
        } else {
            $from = $fromOrBounds;
        }

        return $this->when(
            $from && $to,
            fn(self $q) => $q->whereBetween('page_views.created_at', [$from, $to])
        );
    }
}
