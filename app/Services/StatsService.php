<?php

namespace App\Services;

use App\Enums\StatsSort;
use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class StatsService
{
    private const string CONTEXT_BLOG = 'blog';

    private const string CONTEXT_POST = 'post';

    private const string CONTEXT_VISITOR = 'visitor';

    private const string NON_EMPTY_STRING_SQL = "IS NOT NULL AND %s <> ''";

    private ?string $blogMorphClass = null;

    private ?string $postMorphClass = null;

    /**
     * Returns aggregated views for blogs within the given period.
     *
     * @return Collection<int, array{blog_id:int,name:string,owner_id:int,owner_name:string,views:int,unique_views:int,post_views:int,unique_post_views:int}>
     */
    public function blogViews(StatsCriteria $criteria): Collection
    {
        [$from, $to] = $criteria->range->bounds();
        $postViewsSubquery = $this->buildPostViewsSubquery($from, $to);

        $query = $this->buildBlogStatsQuery($from, $to, $postViewsSubquery)
            ->when($criteria->bloggerId, fn(Builder $q) => $q->where('blogs.user_id', $criteria->bloggerId))
            ->when($criteria->blogId, fn(Builder $q) => $q->where('blogs.id', $criteria->blogId));

        $this->applySort($query, $criteria->sort, self::CONTEXT_BLOG);
        $this->applyLimit($query, $criteria->limit);

        /** @var Collection<int, array{blog_id:int,name:string,owner_id:int,owner_name:string,views:int,unique_views:int,post_views:int,unique_post_views:int}> $rows */
        $rows = collect($query->get()->map($this->formatBlogStatsRow(...)));

        return $rows;
    }

    private function buildPostViewsSubquery(
        DateTimeInterface $from,
        DateTimeInterface $to,
    ): QueryBuilder|Builder {
        $postClass = $this->getPostMorphClass();
        $uniqueViewerKeySql = $this->uniqueViewerKeySql('page_views');

        return PageView::query()
            ->selectRaw(
                "posts.blog_id, COUNT(page_views.id) as views, COUNT(DISTINCT ($uniqueViewerKeySql)) as unique_views",
            )
            ->join('posts', function ($join) use ($postClass, $from, $to) {
                $join->on('posts.id', '=', 'page_views.viewable_id')
                    ->where('page_views.viewable_type', '=', $postClass)
                    ->whereBetween('page_views.created_at', [$from, $to]);
            })
            ->groupBy('posts.blog_id');
    }

    private function getPostMorphClass(): string
    {
        return $this->postMorphClass ??= (new Post)->getMorphClass();
    }

    private function uniqueViewerKeySql(string $tableAlias): string
    {
        $visitorIdPresent = sprintf(self::NON_EMPTY_STRING_SQL, $tableAlias . '.visitor_id');
        $fingerprintPresent = sprintf(self::NON_EMPTY_STRING_SQL, $tableAlias . '.fingerprint');
        $sessionIdPresent = sprintf(self::NON_EMPTY_STRING_SQL, $tableAlias . '.session_id');

        return "(
            CASE
              WHEN {$tableAlias}.user_id IS NOT NULL THEN CONCAT('U:', {$tableAlias}.user_id)
              WHEN {$tableAlias}.visitor_id {$visitorIdPresent} THEN CONCAT('V:', {$tableAlias}.visitor_id)
              WHEN {$tableAlias}.fingerprint {$fingerprintPresent} THEN CONCAT('F:', {$tableAlias}.fingerprint)
              WHEN {$tableAlias}.session_id {$sessionIdPresent} THEN CONCAT('S:', {$tableAlias}.session_id)
              ELSE CONCAT('I:', COALESCE({$tableAlias}.ip_address, ''))
            END
        )";
    }

    private function buildBlogStatsQuery(
        DateTimeInterface $from,
        DateTimeInterface $to,
        QueryBuilder|Builder $postViewsSubquery,
    ): Builder {
        $blogClass = $this->getBlogMorphClass();
        $uniqueViewerKeySql = $this->uniqueViewerKeySql('blog_views');

        return Blog::query()
            ->selectRaw(
                'blogs.id as blog_id, blogs.name, blogs.user_id as owner_id, users.name as owner_name,' .
                ' COALESCE(COUNT(blog_views.id), 0) as views,' .
                " COALESCE(COUNT(DISTINCT ($uniqueViewerKeySql)), 0) as unique_views," .
                ' COALESCE(post_views_agg.views, 0) as post_views,' .
                ' COALESCE(post_views_agg.unique_views, 0) as unique_post_views',
            )
            ->leftJoin('users', 'users.id', '=', 'blogs.user_id')
            ->leftJoinSub($postViewsSubquery, 'post_views_agg', function ($join) {
                $join->on('post_views_agg.blog_id', '=', 'blogs.id');
            })
            ->leftJoin('page_views as blog_views', function ($join) use ($blogClass, $from, $to) {
                $join->on('blogs.id', '=', 'blog_views.viewable_id')
                    ->where('blog_views.viewable_type', '=', $blogClass)
                    ->whereBetween('blog_views.created_at', [$from, $to]);
            })
            ->groupBy(
                'blogs.id',
                'blogs.name',
                'blogs.user_id',
                'users.name',
                'post_views_agg.views',
                'post_views_agg.unique_views',
            );
    }

    private function getBlogMorphClass(): string
    {
        return $this->blogMorphClass ??= (new Blog)->getMorphClass();
    }

    private function applySort(Builder|QueryBuilder $query, StatsSort $sort, string $context): void
    {
        $columnMap = match ($context) {
            self::CONTEXT_BLOG => ['name' => 'blogs.name', 'views' => 'views'],
            self::CONTEXT_POST => ['name' => 'posts.title', 'views' => 'views'],
            self::CONTEXT_VISITOR => ['name' => 'visitor_label', 'views' => 'post_views'],
        };

        match ($sort) {
            StatsSort::ViewsAsc => $query->orderBy($columnMap['views'], 'asc'),
            StatsSort::NameAsc, StatsSort::TitleAsc => $query->orderBy($columnMap['name'], 'asc'),
            StatsSort::NameDesc, StatsSort::TitleDesc => $query->orderBy($columnMap['name'], 'desc'),
            default => $query->orderBy($columnMap['views'], 'desc'),
        };
    }

    private function applyLimit(Builder|QueryBuilder $query, ?int $limit): void
    {
        if ($limit !== null) {
            $query->limit(max(1, $limit));
        }
    }

    /**
     * Returns aggregated views for posts within a blog and period.
     *
     * @return Collection<int, array{post_id:int,title:string,views:int,unique_views:int}>
     */
    public function postViews(StatsCriteria $criteria): Collection
    {
        [$from, $to] = $criteria->range->bounds();
        $postClass = $this->getPostMorphClass();
        $uniqueViewerKeySql = $this->uniqueViewerKeySql('page_views');

        $query = PageView::query()
            ->selectRaw(
                "posts.id as post_id, posts.title, COUNT(page_views.id) as views, COUNT(DISTINCT ($uniqueViewerKeySql)) as unique_views",
            )
            ->join('posts', function ($j) use ($postClass) {
                $j->on('posts.id', '=', 'page_views.viewable_id')
                    ->where('page_views.viewable_type', '=', $postClass);
            })
            ->when($criteria->blogId, fn($q) => $q->where('posts.blog_id', $criteria->blogId))
            ->when($criteria->bloggerId, function ($q) use ($criteria) {
                $q->join('blogs', 'blogs.id', '=', 'posts.blog_id')
                    ->where('blogs.user_id', $criteria->bloggerId);
            })
            ->whereBetween('page_views.created_at', [$from, $to])
            ->groupBy('posts.id', 'posts.title');

        $this->applySort($query, $criteria->sort, self::CONTEXT_POST);
        $this->applyLimit($query, $criteria->limit);

        /** @var Collection<int, array{post_id:int,title:string,views:int,unique_views:int}> $rows */
        $rows = collect($query->get()->map(function ($row) {
            return [
                'post_id' => (int)$row->post_id,
                'title' => (string)$row->title,
                'views' => (int)$row->views,
                'unique_views' => (int)$row->unique_views,
            ];
        }));

        return $rows;
    }

    /**
     * Returns aggregated views by visitor, split into blog and post views.
     *
     * @return Collection<int, array{visitor_label:string,blog_views:int,post_views:int,views:int}>
     */
    public function visitorViews(StatsCriteria $criteria): Collection
    {
        [$startDate, $endDate] = $criteria->range->bounds();
        $blogMorphClass = $this->getBlogMorphClass();
        $postMorphClass = $this->getPostMorphClass();

        $query = $this->buildBaseQuery($criteria, $blogMorphClass, $postMorphClass, $startDate, $endDate);
        $this->applySelectClause($query, $criteria, $blogMorphClass, $postMorphClass);
        $this->applyHavingClause($query, $criteria, $blogMorphClass, $postMorphClass);
        $this->applySort($query, $criteria->sort, self::CONTEXT_VISITOR);
        $this->applyLimit($query, $criteria->limit);

        /** @var Collection<int, array{visitor_label:string,blog_views:int,post_views:int,views:int}> $rows */
        $rows = collect(
            $query->get()->map(static function ($row) {
                return [
                    'visitor_label' => (string)$row->visitor_label,
                    'blog_views' => (int)$row->blog_views,
                    'post_views' => (int)$row->post_views,
                    'views' => (int)$row->views,
                ];
            }),
        );

        return $rows;
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
            ->groupBy('visitor_label', $visitorLabelColumn);

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

        if ($criteria->blogId === null) {
            // No blog filter: count all blog and post views per visitor.
            $query->selectRaw(
                "COALESCE(users.name, $visitorLabelColumn) as visitor_label," .
                ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END) as blog_views,' .
                ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END) as post_views,' .
                ' (COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END) + ' .
                '  COUNT(DISTINCT CASE WHEN page_views.viewable_type = ? THEN page_views.viewable_id END)) as views',
                [
                    $blogMorphClass,
                    $postMorphClass,
                    $blogMorphClass,
                    $postMorphClass,
                ],
            );
        } else {
            // With blog filter: restrict counts (and rows) to the selected blog.
            $query->selectRaw(
                "COALESCE(users.name, $visitorLabelColumn) as visitor_label," .
                ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ?' .
                ' AND page_views.viewable_id = ? THEN page_views.viewable_id END) as blog_views,' .
                ' COUNT(DISTINCT CASE WHEN page_views.viewable_type = ?' .
                ' AND posts.blog_id = ? THEN page_views.viewable_id END) as post_views,' .
                ' (COUNT(DISTINCT CASE WHEN page_views.viewable_type = ?' .
                ' AND page_views.viewable_id = ? THEN page_views.viewable_id END) + ' .
                '  COUNT(DISTINCT CASE WHEN page_views.viewable_type = ?' .
                ' AND posts.blog_id = ? THEN page_views.viewable_id END)) as views',
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
            // Only keep visitors who have at least one matching unique blog or post view for that blog.
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

    private function formatBlogStatsRow(object $row): array
    {
        return [
            'blog_id' => (int)$row->blog_id,
            'name' => (string)$row->name,
            'owner_id' => (int)$row->owner_id,
            'owner_name' => (string)($row->owner_name ?? ''),
            'views' => (int)$row->views,
            'unique_views' => (int)($row->unique_views ?? 0),
            'post_views' => (int)$row->post_views,
            'unique_post_views' => (int)($row->unique_post_views ?? 0),
        ];
    }
}
