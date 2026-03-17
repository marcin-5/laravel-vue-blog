<?php

namespace App\Queries\Stats;

use App\DataTransferObjects\Stats\PostStatsRow;
use App\Enums\StatsSort;
use App\Models\PageView;
use App\Models\Post;
use App\Services\Stats\UniqueViewerKeyBuilder;
use App\Services\StatsCriteria;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostViewsQuery
{
    private ?string $postMorphClass = null;

    public function __construct(
        private readonly UniqueViewerKeyBuilder $uniqueViewerKeyBuilder,
    ) {}

    /**
     * @return Collection<int, array{post_id:int,title:string,views:int,unique_views:int,bot_views:int,anonymous_views:int,markdown_views:int}>
     */
    public function execute(StatsCriteria $criteria): Collection
    {
        $bounds = $criteria->range->bounds();
        $from = $bounds[0] ?? null;
        $to = $bounds[1] ?? null;

        $query = $this
            ->buildQuery($from, $to)
            ->when($criteria->blogId, fn(Builder $q) => $q->where('posts.blog_id', $criteria->blogId))
            ->when($criteria->bloggerId, function (Builder $q) use ($criteria) {
                $q
                    ->join('blogs', 'blogs.id', '=', 'posts.blog_id')
                    ->where('blogs.user_id', $criteria->bloggerId);
            });

        $this->applySort($query, $criteria->sort);
        $this->applyLimit($query, $criteria->limit);

        return $query->get()->map(fn(object $row) => PostStatsRow::fromRow($row)->toArray());
    }

    private function buildQuery(?DateTimeInterface $from, ?DateTimeInterface $to): Builder
    {
        $postClass = $this->getPostMorphClass();
        $uniqueViewerKeySql = $this->uniqueViewerKeyBuilder->build('page_views');

        $botSub = $this->buildAggregateSubquery('bot_views', 'bot_views', $postClass, $from, $to);
        $anonSub = $this->buildAggregateSubquery('anonymous_views', 'anonymous_views', $postClass, $from, $to);
        $markdownSub = $this->buildAggregateSubquery('markdown_views', 'markdown_views', $postClass, $from, $to);

        return PageView::query()
            ->selectRaw(
                "posts.id as post_id, posts.title, COUNT(page_views.id) as views, COUNT(DISTINCT ($uniqueViewerKeySql)) as unique_views, " .
                'COALESCE(bot.bot_views, 0) as bot_views, COALESCE(anon.anonymous_views, 0) as anonymous_views, COALESCE(markdown.markdown_views, 0) as markdown_views',
            )
            ->join('posts', function ($join) use ($postClass) {
                $join
                    ->on('posts.id', '=', 'page_views.viewable_id')
                    ->where('page_views.viewable_type', '=', $postClass);
            })
            ->leftJoinSub($botSub, 'bot', 'bot.viewable_id', '=', 'posts.id')
            ->leftJoinSub($anonSub, 'anon', 'anon.viewable_id', '=', 'posts.id')
            ->leftJoinSub($markdownSub, 'markdown', 'markdown.viewable_id', '=', 'posts.id')
            ->when($from && $to, fn($q) => $q->whereBetween('page_views.created_at', [$from, $to]))
            ->groupBy('posts.id', 'posts.title', 'bot.bot_views', 'anon.anonymous_views', 'markdown.markdown_views');
    }

    private function getPostMorphClass(): string
    {
        return $this->postMorphClass ??= (new Post)->getMorphClass();
    }

    private function buildAggregateSubquery(
        string $table,
        string $columnAlias,
        string $postClass,
        ?DateTimeInterface $from,
        ?DateTimeInterface $to,
    ): QueryBuilder {
        return DB::query()
            ->from($table)
            ->selectRaw("viewable_id, SUM(hits) as {$columnAlias}")
            ->where('viewable_type', '=', $postClass)
            ->when($from && $to, fn($q) => $q->whereBetween('last_seen_at', [$from, $to]))
            ->groupBy('viewable_id');
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
