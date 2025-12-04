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

        $this->applyBlogSort($query, $criteria->sort);

        if ($criteria->limit !== null) {
            $query->limit(max(1, (int)$criteria->limit));
        }

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
                ' COALESCE(SUM(post_views_agg.views), 0) as post_views',
            )
            ->leftJoin('users', 'users.id', '=', 'blogs.user_id')
            ->leftJoin('page_views as blog_views', function ($join) use ($blogClass, $from, $to) {
                $join->on('blogs.id', '=', 'blog_views.viewable_id')
                    ->where('blog_views.viewable_type', '=', $blogClass)
                    ->whereBetween('blog_views.created_at', [$from, $to]);
            })
            ->leftJoinSub($postViewsSubquery, 'post_views_agg', function ($join) {
                $join->on('post_views_agg.blog_id', '=', 'blogs.id');
            })
            ->groupBy('blogs.id', 'blogs.name', 'blogs.user_id', 'users.name');
    }

    /**
     * @param Builder|QueryBuilder $query
     * @param StatsSort $sort
     */
    private function applyBlogSort(Builder|QueryBuilder $query, StatsSort $sort): void
    {
        match ($sort) {
            StatsSort::ViewsAsc => $query->orderBy('views', 'asc'),
            StatsSort::NameAsc => $query->orderBy('blogs.name', 'asc'),
            StatsSort::NameDesc => $query->orderBy('blogs.name', 'desc'),
            default => $query->orderBy('views', 'desc'),
        };
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

        $this->applyPostSort($query, $criteria->sort);

        if ($criteria->limit !== null) {
            $query->limit(max(1, (int)$criteria->limit));
        }

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

    private function applyPostSort(QueryBuilder|Builder $query, StatsSort $sort): void
    {
        match ($sort) {
            StatsSort::ViewsAsc => $query->orderBy('views', 'asc'),
            StatsSort::TitleAsc => $query->orderBy('posts.title', 'asc'),
            StatsSort::TitleDesc => $query->orderBy('posts.title', 'desc'),
            default => $query->orderBy('views', 'desc'),
        };
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
