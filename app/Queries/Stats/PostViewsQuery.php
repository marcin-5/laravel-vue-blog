<?php

namespace App\Queries\Stats;

use App\DataTransferObjects\Stats\PostStatsRow;
use App\Enums\StatsSort;
use App\Models\PageView;
use App\Models\Post;
use App\Services\Stats\UniqueViewerKeyBuilder;
use App\Services\StatsCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PostViewsQuery
{
    private ?string $postMorphClass = null;

    public function __construct(
        private readonly UniqueViewerKeyBuilder $uniqueViewerKeyBuilder,
    ) {
    }

    /**
     * @return Collection<int, array{post_id:int,title:string,views:int,unique_views:int}>
     */
    public function execute(StatsCriteria $criteria): Collection
    {
        [$from, $to] = $criteria->range->bounds();
        $postClass = $this->getPostMorphClass();
        $uniqueViewerKeySql = $this->uniqueViewerKeyBuilder->build('page_views');

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

        $this->applySort($query, $criteria->sort);
        $this->applyLimit($query, $criteria->limit);

        return $query->get()->map(fn(object $row) => PostStatsRow::fromRow($row)->toArray());
    }

    private function getPostMorphClass(): string
    {
        return $this->postMorphClass ??= (new Post)->getMorphClass();
    }

    private function applySort(Builder $query, StatsSort $sort): void
    {
        match ($sort) {
            StatsSort::ViewsAsc => $query->orderBy('views', 'asc'),
            StatsSort::TitleAsc => $query->orderBy('posts.title', 'asc'),
            StatsSort::TitleDesc => $query->orderBy('posts.title', 'desc'),
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
