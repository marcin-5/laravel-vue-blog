<?php

namespace App\Queries\Stats;

use App\DataTransferObjects\Stats\VisitorStatsRow;
use App\Enums\StatsSort;
use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\PageView;
use App\Models\Post;
use App\Services\StatsCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VisitorViewsQuery
{
    private ?string $blogMorphClass = null;

    private ?string $postMorphClass = null;

    /**
     * @return Collection<int, array{visitor_label:string,blog_views:int,post_views:int,views:int,lifetime_views:int,user_agent:string|null}>
     */
    public function execute(StatsCriteria $criteria): Collection
    {
        [$startDate, $endDate] = $criteria->range->bounds();
        $blogMorphClass = $this->getBlogMorphClass();
        $postMorphClass = $this->getPostMorphClass();

        $query = $this->buildBaseQuery($criteria, $blogMorphClass, $postMorphClass, $startDate, $endDate);
        $this->applySelectClause($query, $criteria, $blogMorphClass, $postMorphClass);
        $this->applyHavingClause($query, $criteria, $blogMorphClass, $postMorphClass);
        $this->applySort($query, $criteria->sort);
        $this->applyLimit($query, $criteria->limit);

        return $query->get()->map(fn (object $row) => VisitorStatsRow::fromRow($row)->toArray());
    }

    private function getBlogMorphClass(): string
    {
        return $this->blogMorphClass ??= (new Blog)->getMorphClass();
    }

    private function getPostMorphClass(): string
    {
        return $this->postMorphClass ??= (new Post)->getMorphClass();
    }

    private function buildBaseQuery(
        StatsCriteria $criteria,
        string $blogMorphClass,
        string $postMorphClass,
        string $startDate,
        string $endDate,
    ): Builder {
        $visitorLabelColumn = $criteria->visitorGroupBy === 'fingerprint'
            ? 'page_views.fingerprint'
            : 'page_views.visitor_id';

        $query = PageView::query()
            ->leftJoin('users', 'users.id', '=', 'page_views.user_id')
            ->leftJoinSub(
                NewsletterSubscription::select('visitor_id', DB::raw('MAX(email) as email'))->groupBy('visitor_id'),
                'ns',
                'page_views.visitor_id',
                '=',
                'ns.visitor_id',
            )
            ->leftJoin('posts', function ($join) use ($postMorphClass) {
                $join->on('posts.id', '=', 'page_views.viewable_id')
                    ->where('page_views.viewable_type', '=', $postMorphClass);
            })
            ->whereBetween('page_views.created_at', [$startDate, $endDate])
            ->when($criteria->bloggerId, function (Builder $query) use ($criteria, $blogMorphClass, $postMorphClass) {
                $query->where(function (Builder $innerQuery) use ($criteria, $blogMorphClass, $postMorphClass) {
                    $innerQuery
                        ->whereExists(function (QueryBuilder $existsQuery) use ($criteria, $blogMorphClass) {
                            $existsQuery
                                ->from('blogs')
                                ->whereColumn('blogs.id', 'page_views.viewable_id')
                                ->where('page_views.viewable_type', '=', $blogMorphClass)
                                ->where('blogs.user_id', '=', $criteria->bloggerId);
                        })
                        ->orWhereExists(function (QueryBuilder $existsQuery) use ($criteria, $postMorphClass) {
                            $existsQuery
                                ->from('blogs')
                                ->join('posts', 'posts.blog_id', '=', 'blogs.id')
                                ->whereColumn('posts.id', 'page_views.viewable_id')
                                ->where('page_views.viewable_type', '=', $postMorphClass)
                                ->where('blogs.user_id', '=', $criteria->bloggerId);
                        });
                });
            })
            ->groupBy('visitor_label', $visitorLabelColumn, 'page_views.user_agent');

        return $query;
    }

    private function applySelectClause(
        Builder $query,
        StatsCriteria $criteria,
        string $blogMorphClass,
        string $postMorphClass,
    ): void {
        $visitorLabelColumn = $criteria->visitorGroupBy === 'fingerprint'
            ? 'page_views.fingerprint'
            : 'page_views.visitor_id';

        $groupByColumn = $criteria->visitorGroupBy === 'fingerprint'
            ? 'fingerprint'
            : 'visitor_id';

        $totalViewsRaw = "(SELECT COUNT(*) FROM page_views as pv2 WHERE pv2.$groupByColumn = page_views.$groupByColumn) as lifetime_views";

        if ($criteria->blogId === null) {
            $query->selectRaw(
                "COALESCE(users.name, ns.email, $visitorLabelColumn) as visitor_label,".
                ' page_views.user_agent,'.
                ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END) as blog_views,'.
                ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END) as post_views,'.
                ' (COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END) + '.
                '  COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END)) as views,'.
                $totalViewsRaw,
                [
                    $blogMorphClass,
                    $postMorphClass,
                    $blogMorphClass,
                    $postMorphClass,
                ],
            );
        } else {
            $query->selectRaw(
                "COALESCE(users.name, ns.email, $visitorLabelColumn) as visitor_label,".
                ' page_views.user_agent,'.
                ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ?'.
                ' AND page_views.viewable_id = ? THEN page_views.viewable_id END) as blog_views,'.
                ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ?'.
                ' AND posts.blog_id = ? THEN page_views.viewable_id END) as post_views,'.
                ' (COUNT(DISTINCT CASE WHEN page_views.viewable_type = ?'.
                ' AND page_views.viewable_id = ? THEN page_views.viewable_id END) + '.
                '  COUNT(DISTINCT CASE WHEN page_views.viewable_type = ?'.
                ' AND posts.blog_id = ? THEN page_views.viewable_id END)) as views,'.
                $totalViewsRaw,
                [
                    $blogMorphClass,
                    $criteria->blogId,
                    $postMorphClass,
                    $criteria->blogId,
                    $blogMorphClass,
                    $criteria->blogId,
                    $postMorphClass,
                    $criteria->blogId,
                ],
            );
        }
    }

    private function applyHavingClause(
        Builder $query,
        StatsCriteria $criteria,
        string $blogMorphClass,
        string $postMorphClass,
    ): void {
        if ($criteria->blogId !== null) {
            $query->havingRaw(
                '(
                COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? AND page_views.viewable_id = ? THEN page_views.viewable_id END) > 0
                OR COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? AND posts.blog_id = ? THEN page_views.viewable_id END) > 0
            )',
                [
                    $blogMorphClass,
                    $criteria->blogId,
                    $postMorphClass,
                    $criteria->blogId,
                ],
            );
        }
    }

    private function applySort(Builder $query, StatsSort $sort): void
    {
        match ($sort) {
            StatsSort::ViewsAsc => $query->orderBy('post_views', 'asc'),
            StatsSort::NameAsc => $query->orderBy('visitor_label', 'asc'),
            StatsSort::NameDesc => $query->orderBy('visitor_label', 'desc'),
            default => $query->orderBy('post_views', 'desc'),
        };
    }

    private function applyLimit(Builder $query, ?int $limit): void
    {
        if ($limit !== null) {
            $query->limit(max(1, $limit));
        }
    }
}
