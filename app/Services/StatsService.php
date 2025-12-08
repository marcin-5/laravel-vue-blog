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

    /**
     * Returns aggregated views for blogs within the given period.
     *
     * @return Collection<int, array{blog_id:int,name:string,owner_id:int,owner_name:string,views:int,post_views:int}>
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

        /** @var Collection<int, array{blog_id:int,name:string,owner_id:int,owner_name:string,views:int,post_views:int}> $rows */
        $rows = collect($query->get()->map($this->formatBlogStatsRow(...)));

        return $rows;
    }

    private function buildPostViewsSubquery(
        DateTimeInterface $from,
        DateTimeInterface $to,
    ): QueryBuilder|Builder {
        $postClass = new Post()->getMorphClass();

        return PageView::query()
            ->selectRaw('posts.blog_id, COUNT(page_views.id) as views')
            ->join('posts', function ($join) use ($postClass, $from, $to) {
                $join->on('posts.id', '=', 'page_views.viewable_id')
                    ->where('page_views.viewable_type', '=', $postClass)
                    ->whereBetween('page_views.created_at', [$from, $to]);
            })
            ->groupBy('posts.blog_id');
    }

    private function buildBlogStatsQuery(
        DateTimeInterface $from,
        DateTimeInterface $to,
        QueryBuilder|Builder $postViewsSubquery,
    ): Builder {
        $blogClass = new Blog()->getMorphClass();

        return Blog::query()
            ->selectRaw(
                'blogs.id as blog_id, blogs.name, blogs.user_id as owner_id, users.name as owner_name,' .
                ' COALESCE(COUNT(blog_views.id), 0) as views,' .
                ' COALESCE(post_views_agg.views, 0) as post_views',
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
            ->groupBy('blogs.id', 'blogs.name', 'blogs.user_id', 'users.name', 'post_views_agg.views');
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
     * @return Collection<int, array{post_id:int,title:string,views:int}>
     */
    public function postViews(StatsCriteria $criteria): Collection
    {
        [$from, $to] = $criteria->range->bounds();
        $postClass = (new Post)->getMorphClass();

        $query = PageView::query()
            ->selectRaw('posts.id as post_id, posts.title, COUNT(page_views.id) as views')
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

        /** @var Collection<int, array{post_id:int,title:string,views:int}> $rows */
        $rows = collect($query->get()->map(function ($row) {
            return [
                'post_id' => (int)$row->post_id,
                'title' => (string)$row->title,
                'views' => (int)$row->views,
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
        $blogMorphClass = (new Blog)->getMorphClass();
        $postMorphClass = (new Post)->getMorphClass();

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
            ->groupBy('visitor_label');

        return $query;
    }

    private function applySelectClause(
        Builder $query,
        StatsCriteria $criteria,
        string $blogMorphClass,
        string $postMorphClass,
    ): void {
        if ($criteria->blogId === null) {
            // No blog filter: count all blog and post views per visitor.
            $query->selectRaw(
                'COALESCE(users.name, page_views.visitor_id) as visitor_label,' .
                ' SUM(CASE WHEN page_views.viewable_type = ? THEN 1 ELSE 0 END) as blog_views,' .
                ' SUM(CASE WHEN page_views.viewable_type = ? THEN 1 ELSE 0 END) as post_views,' .
                ' COUNT(page_views.id) as views',
                [
                    $blogMorphClass,
                    $postMorphClass,
                ],
            );
        } else {
            // With blog filter: restrict counts (and rows) to the selected blog.
            $query->selectRaw(
                'COALESCE(users.name, page_views.visitor_id) as visitor_label,' .
                ' SUM(CASE WHEN page_views.viewable_type = ?' .
                ' AND page_views.viewable_id = ? THEN 1 ELSE 0 END) as blog_views,' .
                ' SUM(CASE WHEN page_views.viewable_type = ?' .
                ' AND posts.blog_id = ? THEN 1 ELSE 0 END) as post_views,' .
                ' COUNT(page_views.id) as views',
                [
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
            // Only keep visitors who have at least one matching blog or post view for that blog.
            $query->havingRaw(
                'SUM(CASE WHEN page_views.viewable_type = ?' .
                ' AND page_views.viewable_id = ? THEN 1 ELSE 0 END) > 0' .
                ' OR SUM(CASE WHEN page_views.viewable_type = ?' .
                ' AND posts.blog_id = ? THEN 1 ELSE 0 END) > 0',
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
            'post_views' => (int)$row->post_views,
        ];
    }
}
