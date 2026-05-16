<?php

namespace App\Services;

use App\DataTransferObjects\Stats\StatsCriteria;
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
        if ($criteria->visitorType !== 'all') {
            return $this->visitorViews($criteria);
        }

        return $this->visitorViews(
            new StatsCriteria(
                range: $criteria->range,
                bloggerId: $criteria->bloggerId,
                blogId: $criteria->blogId,
                limit: $criteria->limit,
                sort: $criteria->sort,
                visitorGroupBy: $criteria->visitorGroupBy,
                visitorType: 'special',
            ),
        );
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
