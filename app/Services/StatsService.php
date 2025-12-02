<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StatsService
{
    /**
     * Returns aggregated views for blogs within the given period.
     *
     * @param string $range One of: today, week, month, half_year, year
     * @param int|null $bloggerId Optional user ID to filter blogs by owner
     * @param int|null $blogId Optional single blog filter
     * @param int|null $limit Limit number of rows (null = all)
     * @param string $sort One of: views_desc, views_asc, name_asc, name_desc
     * @return Collection<int, array{blog_id:int,name:string,owner_id:int,owner_name:string,views:int}>
     */
    public function blogViews(
        string $range,
        ?int $bloggerId = null,
        ?int $blogId = null,
        ?int $limit = 5,
        string $sort = 'views_desc',
    ): Collection {
        [$from, $to] = $this->periodBounds($range);

        $blogClass = new Blog()->getMorphClass();

        $query = PageView::query()
            ->selectRaw(
                'blogs.id as blog_id, blogs.name, blogs.user_id as owner_id, users.name as owner_name, COUNT(page_views.id) as views',
            )
            ->join('blogs', function ($j) use ($blogClass) {
                $j->on('blogs.id', '=', 'page_views.viewable_id')
                    ->where('page_views.viewable_type', '=', $blogClass);
            })
            ->leftJoin('users', 'users.id', '=', 'blogs.user_id')
            ->whereBetween('page_views.created_at', [$from, $to])
            ->when($bloggerId, fn($q) => $q->where('blogs.user_id', $bloggerId))
            ->when($blogId, fn($q) => $q->where('blogs.id', $blogId))
            ->groupBy('blogs.id', 'blogs.name', 'blogs.user_id', 'users.name');

        $this->applyBlogSort($query, $sort);

        if ($limit !== null) {
            $query->limit(max(1, (int)$limit));
        }

        /** @var Collection<int, array{blog_id:int,name:string,owner_id:int,owner_name:string,views:int}> $rows */
        $rows = collect($query->get()->map(function ($row) {
            return [
                'blog_id' => (int)$row->blog_id,
                'name' => (string)$row->name,
                'owner_id' => (int)$row->owner_id,
                'owner_name' => (string)($row->owner_name ?? ''),
                'views' => (int)$row->views,
            ];
        }));

        return $rows;
    }

    /**
     * @return array{0:CarbonImmutable,1:CarbonImmutable}
     */
    private function periodBounds(string $range): array
    {
        $to = CarbonImmutable::now();
        return match ($range) {
            'today' => [$to->subDay(), $to],
            'week' => [$to->subWeek(), $to],
            'month' => [$to->subMonth(), $to],
            'half_year' => [$to->subMonths(6), $to],
            'year' => [$to->subYear(), $to],
            default => [$to->subWeek(), $to],
        };
    }

    /**
     * @param Builder|QueryBuilderContract $query
     */
    private function applyBlogSort($query, string $sort): void
    {
        match ($sort) {
            'views_asc' => $query->orderBy('views', 'asc'),
            'name_asc' => $query->orderBy('blogs.name', 'asc'),
            'name_desc' => $query->orderBy('blogs.name', 'desc'),
            default => $query->orderBy('views', 'desc'),
        };
    }

    /**
     * Returns aggregated views for posts within a blog and period.
     *
     * @param string $range One of: week, month, half_year, year
     * @param int|null $bloggerId Optional user ID to filter posts by blog owner
     * @param int|null $blogId Optional blog ID filter
     * @param int|null $limit Limit number of rows (null = all)
     * @param string $sort One of: views_desc, views_asc, title_asc, title_desc
     * @return Collection<int, array{post_id:int,title:string,views:int}>
     */
    public function postViews(
        string $range,
        ?int $bloggerId = null,
        ?int $blogId = null,
        ?int $limit = 5,
        string $sort = 'views_desc',
    ): Collection {
        [$from, $to] = $this->periodBounds($range);

        $postClass = (new Post())->getMorphClass();

        $query = PageView::query()
            ->selectRaw('posts.id as post_id, posts.title, COUNT(page_views.id) as views')
            ->join('posts', function ($j) use ($postClass) {
                $j->on('posts.id', '=', 'page_views.viewable_id')
                    ->where('page_views.viewable_type', '=', $postClass);
            })
            ->when($blogId, fn($q) => $q->where('posts.blog_id', $blogId))
            ->when($bloggerId, function ($q) use ($bloggerId) {
                $q->join('blogs', 'blogs.id', '=', 'posts.blog_id')
                    ->where('blogs.user_id', $bloggerId);
            })
            ->whereBetween('page_views.created_at', [$from, $to])
            ->groupBy('posts.id', 'posts.title');

        $this->applyPostSort($query, $sort);

        if ($limit !== null) {
            $query->limit(max(1, (int)$limit));
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

    /**
     * @param Builder|QueryBuilderContract $query
     * @param string $sort
     */
    private function applyPostSort(QueryBuilderContract|Builder $query, string $sort): void
    {
        match ($sort) {
            'views_asc' => $query->orderBy('views', 'asc'),
            'title_asc' => $query->orderBy('posts.title', 'asc'),
            'title_desc' => $query->orderBy('posts.title', 'desc'),
            default => $query->orderBy('views', 'desc'),
        };
    }
}
