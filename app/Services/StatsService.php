<?php

namespace App\Services;

use App\Models\PageView;
use App\Queries\Stats\BlogViewsQuery;
use App\Queries\Stats\BotViewsQuery;
use App\Queries\Stats\PostViewsQuery;
use App\Queries\Stats\VisitorViewsQuery;
use App\Services\Stats\UniqueViewerKeyBuilder;
use Illuminate\Support\Collection;

readonly class StatsService
{
    public function __construct(
        private UniqueViewerKeyBuilder $uniqueViewerKeyBuilder,
        private BlogViewsQuery $blogViewsQuery,
        private PostViewsQuery $postViewsQuery,
        private VisitorViewsQuery $visitorViewsQuery,
        private BotViewsQuery $botViewsQuery,
    ) {
    }

    /**
     * Returns aggregated views for bots for the given criteria.
     *
     * @return Collection<int, array{user_agent:string,hits:int,last_seen_at:string}>
     */
    public function botViews(StatsCriteria $criteria): Collection
    {
        return $this->botViewsQuery->execute($criteria);
    }

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
     * @return Collection<int, array{post_id:int,title:string,views:int,unique_views:int}>
     */
    public function postViews(StatsCriteria $criteria): Collection
    {
        return $this->postViewsQuery->execute($criteria);
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

    public function countUniqueViews(string $morphClass, int $id): int
    {
        $uniqueViewerKeySql = $this->uniqueViewerKeyBuilder->build('page_views');

        /** @var int $count */
        $count = PageView::query()
            ->where('viewable_type', $morphClass)
            ->where('viewable_id', $id)
            ->selectRaw("COUNT(DISTINCT ($uniqueViewerKeySql)) as cnt")
            ->value('cnt');

        return $count;
    }
}
