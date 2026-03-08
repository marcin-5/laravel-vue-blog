<?php

namespace App\Queries\Stats;

use App\DataTransferObjects\Stats\BlogStatsRow;
use App\Enums\StatsSort;
use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Services\Stats\UniqueViewerKeyBuilder;
use App\Services\StatsCriteria;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class BlogViewsQuery
{
    private ?string $blogMorphClass = null;

    private ?string $postMorphClass = null;

    public function __construct(
        private readonly UniqueViewerKeyBuilder $uniqueViewerKeyBuilder,
    ) {
    }

    /**
     * @return Collection<int, array{blog_id:int,name:string,owner_id:int,owner_name:string,views:int,unique_views:int,post_views:int,unique_post_views:int}>
     */
    public function execute(StatsCriteria $criteria): Collection
    {
        $bounds = $criteria->range->bounds();
        $from = $bounds[0] ?? null;
        $to = $bounds[1] ?? null;

        $blogViewsSubquery = $this->buildBlogViewsSubquery($from, $to);
        $postViewsSubquery = $this->buildPostViewsSubquery($from, $to);

        $query = $this->buildQuery($blogViewsSubquery, $postViewsSubquery)
            ->when($criteria->bloggerId, fn(Builder $q) => $q->where('blogs.user_id', $criteria->bloggerId))
            ->when($criteria->blogId, fn(Builder $q) => $q->where('blogs.id', $criteria->blogId));

        $this->applySort($query, $criteria->sort);
        $this->applyLimit($query, $criteria->limit);

        return $query->get()->map(fn(object $row) => BlogStatsRow::fromRow($row)->toArray());
    }

    private function buildBlogViewsSubquery(
        ?DateTimeInterface $from,
        ?DateTimeInterface $to,
    ): QueryBuilder|Builder {
        $blogClass = $this->getBlogMorphClass();
        $uniqueViewerKeySql = $this->uniqueViewerKeyBuilder->build('page_views');

        return PageView::query()
            ->selectRaw(
                "viewable_id as blog_id, COUNT(id) as views, COUNT(DISTINCT ($uniqueViewerKeySql)) as unique_views",
            )
            ->where('viewable_type', '=', $blogClass)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->groupBy('viewable_id');
    }

    private function getBlogMorphClass(): string
    {
        return $this->blogMorphClass ??= (new Blog)->getMorphClass();
    }

    private function buildPostViewsSubquery(
        ?DateTimeInterface $from,
        ?DateTimeInterface $to,
    ): QueryBuilder|Builder {
        $postClass = $this->getPostMorphClass();
        $uniqueViewerKeySql = $this->uniqueViewerKeyBuilder->build('page_views');

        return PageView::query()
            ->selectRaw('posts.blog_id')
            ->selectRaw('COUNT(page_views.id) as views')
            ->selectRaw("COUNT(DISTINCT ($uniqueViewerKeySql)) as unique_views")
            ->join('posts', function ($join) use ($postClass, $from, $to) {
                $join->on('posts.id', '=', 'page_views.viewable_id')
                    ->where('page_views.viewable_type', '=', $postClass)
                    ->when($from && $to, fn($q) => $q->whereBetween('page_views.created_at', [$from, $to]));
            })
            ->groupBy('posts.blog_id');
    }

    private function getPostMorphClass(): string
    {
        return $this->postMorphClass ??= (new Post)->getMorphClass();
    }

    private function buildQuery(
        QueryBuilder|Builder $blogViewsSubquery,
        QueryBuilder|Builder $postViewsSubquery,
    ): Builder {
        return Blog::query()
            ->select('blogs.id as blog_id', 'blogs.name', 'blogs.user_id as owner_id', 'users.name as owner_name')
            ->selectRaw('COALESCE(blog_views_agg.views, 0) as views')
            ->selectRaw('COALESCE(blog_views_agg.unique_views, 0) as unique_views')
            ->selectRaw('COALESCE(post_views_agg.views, 0) as post_views')
            ->selectRaw('COALESCE(post_views_agg.unique_views, 0) as unique_post_views')
            ->leftJoin('users', 'users.id', '=', 'blogs.user_id')
            ->leftJoinSub($blogViewsSubquery, 'blog_views_agg', 'blog_views_agg.blog_id', '=', 'blogs.id')
            ->leftJoinSub($postViewsSubquery, 'post_views_agg', 'post_views_agg.blog_id', '=', 'blogs.id');
    }

    private function applySort(Builder $query, StatsSort $sort): void
    {
        match ($sort) {
            StatsSort::ViewsAsc => $query->orderBy('views', 'asc'),
            StatsSort::NameAsc => $query->orderBy('blogs.name', 'asc'),
            StatsSort::NameDesc => $query->orderBy('blogs.name', 'desc'),
            default => $query->orderBy('views', 'desc'),
        };
    }

    private function applyLimit(Builder $query, ?int $limit): void
    {
        if ($limit !== null) {
            $query->limit(max(1, $limit));
        }
    }
}
