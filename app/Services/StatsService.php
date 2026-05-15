<?php

namespace App\Services;

use App\Enums\StatsSort;
use App\Queries\Stats\BlogViewsQuery;
use App\Queries\Stats\PostViewsQuery;
use App\Queries\Stats\VisitorViewsQuery;
use Illuminate\Support\Collection;

readonly class StatsService
{
    public function __construct(
        private BlogViewsQuery $blogViewsQuery,
        private PostViewsQuery $postViewsQuery,
        private VisitorViewsQuery $visitorViewsQuery,
    ) {}

    /**
     * Returns aggregated views for blogs within the given period.
     *
     * @return Collection<int, array{blog_id:int,name:string,owner_id:int,owner_name:string,views:int,unique_views:int,post_views:int,unique_post_views:int}>
     */
    public function blogViews(StatsCriteria $criteria): Collection
    {
        return $this->blogViewsQuery->execute($criteria);
    }

    /**
     * Returns aggregated views for posts within a blog and period.
     *
     * @return Collection<int, array{post_id:int,title:string,views:int,unique_views:int,bot_views:int,anonymous_views:int}>
     */
    public function postViews(StatsCriteria $criteria): Collection
    {
        return $this->postViewsQuery->execute($criteria);
    }

    /**
     * Returns special visitors (anonymous and/or bots) with optional merging.
     *
     * @return Collection<int, array{visitor_label:string,blog_views:int,post_views:int,views:int,lifetime_views:int,user_agent:string|null}>
     */
    public function specialVisitorViews(StatsCriteria $criteria): Collection
    {
        if ($criteria->visitorType === 'bots' || $criteria->visitorType === 'anonymous' || $criteria->visitorType === 'markdown') {
            return $this->visitorViews($criteria);
        }

        // 'all' selected -> merge anonymous, bots and markdown
        $anonymous = $this->visitorViews(
            new StatsCriteria(
                range: $criteria->range,
                bloggerId: $criteria->bloggerId,
                blogId: $criteria->blogId,
                limit: null, // merge then apply limit manually
                sort: $criteria->sort,
                visitorGroupBy: $criteria->visitorGroupBy,
                visitorType: 'anonymous',
            ),
        );

        $bots = $this->visitorViews(
            new StatsCriteria(
                range: $criteria->range,
                bloggerId: $criteria->bloggerId,
                blogId: $criteria->blogId,
                limit: null,
                sort: $criteria->sort,
                visitorGroupBy: $criteria->visitorGroupBy,
                visitorType: 'bots',
            ),
        );

        $markdown = $this->visitorViews(
            new StatsCriteria(
                range: $criteria->range,
                bloggerId: $criteria->bloggerId,
                blogId: $criteria->blogId,
                limit: null,
                sort: $criteria->sort,
                visitorGroupBy: $criteria->visitorGroupBy,
                visitorType: 'markdown',
            ),
        );

        $merged = collect();
        foreach ([$anonymous, $bots, $markdown] as $set) {
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
                    $existing['row_id'] = $existing['row_id'] ?? $row['row_id'];
                    if (isset($row['last_seen_at'])) {
                        if (!isset($existing['last_seen_at']) || $row['last_seen_at'] > $existing['last_seen_at']) {
                            $existing['last_seen_at'] = $row['last_seen_at'];
                        }
                    }
                    $merged->put($key, $existing);
                }
            }
        }

        // Apply sorting & limiting to merged collection similar to query
        $specialVisitors = $merged->values();
        $specialVisitors = match ($criteria->sort) {
            StatsSort::ViewsAsc => $specialVisitors->sortBy('views')->values(),
            StatsSort::LastSeenAsc => $specialVisitors->sortBy('last_seen_at')->values(),
            StatsSort::LastSeenDesc => $specialVisitors->sortByDesc('last_seen_at')->values(),
            StatsSort::NameAsc => $specialVisitors->sortBy(
                'visitor_label',
                SORT_NATURAL | SORT_FLAG_CASE,
            )->values(),
            StatsSort::NameDesc => $specialVisitors->sortByDesc(
                'visitor_label',
                SORT_NATURAL | SORT_FLAG_CASE,
            )->values(),
            default => $specialVisitors->sortByDesc('views')->values(),
        };

        if ($criteria->limit !== null) {
            $specialVisitors = $specialVisitors->take(max(1, $criteria->limit))->values();
        }

        return $specialVisitors;
    }

    /**
     * Returns aggregated views by visitor, split into blog and post views.
     *
     * @return Collection<int, array{visitor_label:string,blog_views:int,post_views:int,views:int,lifetime_views:int,user_agent:string|null}>
     */
    public function visitorViews(StatsCriteria $criteria): Collection
    {
        return $this->visitorViewsQuery->execute($criteria);
    }
}
