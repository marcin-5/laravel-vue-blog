<?php

namespace App\Queries\Stats;

use App\DataTransferObjects\Stats\VisitorStatsRow;
use App\Enums\StatsSort;
use App\Models\AnonymousView;
use App\Models\Blog;
use App\Models\BotView;
use App\Models\MarkdownView;
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
        if ($criteria->visitorType === 'bots') {
            return $this->executeAggregatedViews($criteria, BotView::class, 'bot_views');
        }
        if ($criteria->visitorType === 'anonymous') {
            return $this->executeAggregatedViews($criteria, AnonymousView::class, 'anonymous_views');
        }
        if ($criteria->visitorType === 'markdown') {
            return $this->executeAggregatedViews($criteria, MarkdownView::class, 'markdown_views');
        }

        $bounds = $criteria->range->bounds();
        $blogMorphClass = $this->getBlogMorphClass();
        $postMorphClass = $this->getPostMorphClass();

        $query = $this->buildBaseQuery($criteria, $blogMorphClass, $postMorphClass, $bounds);
        $this->applySelectClause($query, $criteria, $blogMorphClass, $postMorphClass);
        $this->applyBlogIdWhereFilter($query, $criteria, $blogMorphClass, $postMorphClass);
        $this->applySort($query, $criteria->sort);
        $this->applyLimit($query, $criteria->limit);

        return $query->get()->map(fn(object $row) => VisitorStatsRow::fromRow($row)->toArray());
    }

    /**
     * @param class-string<BotView|AnonymousView|MarkdownView> $modelClass
     */
    private function executeAggregatedViews(StatsCriteria $criteria, string $modelClass, string $tableName): Collection
    {
        $blogMorphClass = $this->getBlogMorphClass();
        $postMorphClass = $this->getPostMorphClass();
        $bounds = $criteria->range->bounds();

        $isMarkdown = $modelClass === MarkdownView::class;

        $query = $modelClass::query()
            ->when(
                !$isMarkdown,
                fn(Builder $q) => $q->join('user_agents', 'user_agents.id', '=', "{$tableName}.user_agent_id"),
            )
            ->when($bounds, fn(Builder $q) => $q->whereBetween("{$tableName}.last_seen_at", $bounds))
            ->selectRaw(
                ($isMarkdown ? "COALESCE({$tableName}.user_agent, 'Unknown') as visitor_label," : 'user_agents.name as visitor_label,') .
                ($isMarkdown ? "COALESCE({$tableName}.user_agent, 'Unknown') as user_agent," : ' user_agents.name as user_agent,') .
                ' SUM(CASE WHEN viewable_type = ? THEN hits ELSE 0 END) as blog_views,' .
                ' SUM(CASE WHEN viewable_type = ? THEN hits ELSE 0 END) as post_views,' .
                ' SUM(hits) as views,' .
                ' SUM(hits) as lifetime_views,' .
                ' MAX(last_seen_at) as last_seen_at',
                [$blogMorphClass, $postMorphClass],
            )
            ->when(
                $criteria->bloggerId,
                fn(Builder $q) => $this->applyBloggerIdFilter(
                    $q,
                    $criteria,
                    $blogMorphClass,
                    $postMorphClass,
                    $tableName,
                ),
            )
            ->when(
                $criteria->blogId,
                fn(Builder $q) => $this->applyBlogIdFilter($q, $criteria, $blogMorphClass, $postMorphClass, $tableName),
            )
            ->groupByRaw($isMarkdown ? "COALESCE({$tableName}.user_agent, 'Unknown')" : 'user_agents.name');

        $this->applySort($query, $criteria->sort);
        $this->applyLimit($query, $criteria->limit);

        return $query->get()->map(fn(object $row) => VisitorStatsRow::fromRow($row)->toArray());
    }

    private function getBlogMorphClass(): string
    {
        return $this->blogMorphClass ??= (new Blog)->getMorphClass();
    }

    private function getPostMorphClass(): string
    {
        return $this->postMorphClass ??= (new Post)->getMorphClass();
    }

    private function applyBloggerIdFilter(
        Builder $query,
        StatsCriteria $criteria,
        string $blogMorphClass,
        string $postMorphClass,
        string $tableName,
    ): void {
        $query->where(function (Builder $innerQuery) use ($criteria, $blogMorphClass, $postMorphClass, $tableName) {
            $innerQuery
                ->whereExists(function (QueryBuilder $existsQuery) use ($criteria, $blogMorphClass, $tableName) {
                    $existsQuery
                        ->from('blogs')
                        ->whereColumn('blogs.id', "{$tableName}.viewable_id")
                        ->where("{$tableName}.viewable_type", '=', $blogMorphClass)
                        ->where('blogs.user_id', '=', $criteria->bloggerId);
                })
                ->orWhereExists(function (QueryBuilder $existsQuery) use ($criteria, $postMorphClass, $tableName) {
                    $existsQuery
                        ->from('blogs')
                        ->join('posts', 'posts.blog_id', '=', 'blogs.id')
                        ->whereColumn('posts.id', "{$tableName}.viewable_id")
                        ->where("{$tableName}.viewable_type", '=', $postMorphClass)
                        ->where('blogs.user_id', '=', $criteria->bloggerId);
                });
        });
    }

    private function applyBlogIdFilter(
        Builder $query,
        StatsCriteria $criteria,
        string $blogMorphClass,
        string $postMorphClass,
        string $tableName,
    ): void {
        $query->where(function (Builder $innerQuery) use ($criteria, $blogMorphClass, $postMorphClass, $tableName) {
            $innerQuery
                ->where(function (Builder $q) use ($criteria, $blogMorphClass, $tableName) {
                    $q->where("{$tableName}.viewable_type", $blogMorphClass)
                        ->where("{$tableName}.viewable_id", $criteria->blogId);
                })
                ->orWhere(function (Builder $q) use ($criteria, $postMorphClass, $tableName) {
                    $q->where("{$tableName}.viewable_type", $postMorphClass)
                        ->whereExists(function (QueryBuilder $existsQuery) use ($criteria, $tableName) {
                            $existsQuery->from('posts')
                                ->whereColumn('posts.id', "{$tableName}.viewable_id")
                                ->where('posts.blog_id', $criteria->blogId);
                        });
                });
        });
    }

    private function applySort(Builder $query, StatsSort $sort): void
    {
        match ($sort) {
            StatsSort::ViewsAsc => $query->orderBy('views', 'asc'),
            StatsSort::ViewsDesc => $query->orderBy('views', 'desc'),
            StatsSort::NameAsc => $query->orderBy('visitor_label', 'asc'),
            StatsSort::NameDesc => $query->orderBy('visitor_label', 'desc'),
            StatsSort::LastSeenAsc => $query->orderBy('last_seen_at', 'asc'),
            default => $query->orderBy('last_seen_at', 'desc'),
        };
    }

    private function applyLimit(Builder $query, ?int $limit): void
    {
        if ($limit !== null) {
            $query->limit(max(1, $limit));
        }
    }

    private function buildBaseQuery(
        StatsCriteria $criteria,
        string $blogMorphClass,
        string $postMorphClass,
        ?array $bounds,
    ): Builder {
        $visitorLabelColumn = $this->resolveVisitorLabelColumn($criteria);

        return PageView::query()
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
            ->when($bounds, fn(Builder $q) => $q->whereBetween('page_views.created_at', $bounds))
            ->when(
                $criteria->bloggerId,
                fn(Builder $q) => $this->applyBloggerIdFilter(
                    $q,
                    $criteria,
                    $blogMorphClass,
                    $postMorphClass,
                    'page_views',
                ),
            )
            ->groupBy('visitor_label', $visitorLabelColumn, 'page_views.user_agent')
            ->when($criteria->visitorType === 'anonymous', function (Builder $query) {
                $query->whereNull('page_views.user_id');
            });
    }

    private function resolveVisitorLabelColumn(StatsCriteria $criteria): string
    {
        return $criteria->visitorGroupBy === 'fingerprint'
            ? 'page_views.fingerprint'
            : 'page_views.visitor_id';
    }

    private function applySelectClause(
        Builder $query,
        StatsCriteria $criteria,
        string $blogMorphClass,
        string $postMorphClass,
    ): void {
        $visitorLabelColumn = $this->resolveVisitorLabelColumn($criteria);
        $groupByColumn = $criteria->visitorGroupBy === 'fingerprint' ? 'fingerprint' : 'visitor_id';

        $lifetimeViewsSubquery = $this->buildLifetimeViewsSubquery($groupByColumn);
        $visitorLabelSelect = $this->buildVisitorLabelSelect($criteria, $visitorLabelColumn);

        if ($criteria->blogId === null) {
            $this->applySelectWithoutBlogFilter(
                $query,
                $visitorLabelSelect,
                $blogMorphClass,
                $postMorphClass,
                $lifetimeViewsSubquery,
            );
        } else {
            $this->applySelectWithBlogFilter(
                $query,
                $visitorLabelSelect,
                $criteria->blogId,
                $blogMorphClass,
                $postMorphClass,
                $lifetimeViewsSubquery,
            );
        }
    }

    /**
     * Build the lifetime views subquery.
     */
    private function buildLifetimeViewsSubquery(string $groupByColumn): string
    {
        return "(SELECT COUNT(*) FROM page_views as pv2 WHERE pv2.$groupByColumn = page_views.$groupByColumn) as lifetime_views";
    }

    /**
     * Build the visitor label selection clause.
     */
    private function buildVisitorLabelSelect(StatsCriteria $criteria, string $visitorLabelColumn): string
    {
        return match ($criteria->visitorType) {
            'anonymous' => 'page_views.user_agent as visitor_label,',
            default => "COALESCE(users.name, ns.email, $visitorLabelColumn) as visitor_label,",
        };
    }

    /**
     * Apply SELECT clause when no blog filter is present.
     */
    private function applySelectWithoutBlogFilter(
        Builder $query,
        string $visitorLabelSelect,
        string $blogMorphClass,
        string $postMorphClass,
        string $lifetimeViewsSubquery,
    ): void {
        $query->selectRaw(
            $visitorLabelSelect .
            ' page_views.user_agent,' .
            ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END) as blog_views,' .
            ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END) as post_views,' .
            ' (COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END) + ' .
            ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END)) as views,' .
            $lifetimeViewsSubquery,
            [$blogMorphClass, $postMorphClass, $blogMorphClass, $postMorphClass],
        );
    }

    /**
     * Apply SELECT clause when blog filter is present.
     */
    private function applySelectWithBlogFilter(
        Builder $query,
        string $visitorLabelSelect,
        int $blogId,
        string $blogMorphClass,
        string $postMorphClass,
        string $lifetimeViewsSubquery,
    ): void {
        $query->selectRaw(
            $visitorLabelSelect .
            ' page_views.user_agent,' .
            ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? AND page_views.viewable_id = ? THEN page_views.viewable_id END) as blog_views,' .
            ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? AND posts.blog_id = ? THEN page_views.viewable_id END) as post_views,' .
            ' (COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? AND page_views.viewable_id = ? THEN page_views.viewable_id END) + ' .
            ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? AND posts.blog_id = ? THEN page_views.viewable_id END)) as views,' .
            $lifetimeViewsSubquery,
            [$blogMorphClass, $blogId, $postMorphClass, $blogId, $blogMorphClass, $blogId, $postMorphClass, $blogId],
        );
    }

    /**
     * Apply WHERE filter for blog ID.
     * This filters grouped rows based on underlying page view rows.
     */
    private function applyBlogIdWhereFilter(
        Builder $query,
        StatsCriteria $criteria,
        string $blogMorphClass,
        string $postMorphClass,
    ): void {
        if ($criteria->blogId === null) {
            return;
        }

        $query->where(function (Builder $inner) use ($criteria, $blogMorphClass, $postMorphClass) {
            $inner
                ->where(function (Builder $q) use ($criteria, $blogMorphClass) {
                    $q->where('page_views.viewable_type', '=', $blogMorphClass)
                        ->where('page_views.viewable_id', '=', $criteria->blogId);
                })
                ->orWhere(function (Builder $q) use ($criteria, $postMorphClass) {
                    $q->where('page_views.viewable_type', '=', $postMorphClass)
                        ->where('posts.blog_id', '=', $criteria->blogId);
                });
        });
    }
}
