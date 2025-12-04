<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatsRange;
use App\Enums\StatsSort;
use App\Http\Controllers\AuthenticatedController;
use App\Http\Controllers\Concerns\HandlesStatsFilters;
use App\Models\Blog;
use App\Models\User;
use App\Services\StatsCriteria;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends AuthenticatedController
{
    use HandlesStatsFilters;

    public function __construct(private readonly StatsService $stats)
    {
        parent::__construct();
    }

    public function index(Request $request): Response
    {
        $blogFilters = $this->parseStatsFilters($request);
        [
            'range' => $range,
            'sort' => $sort,
            'limit' => $limit,
            'blogger_id' => $bloggerId,
            'blog_id' => $blogId,
        ] = $blogFilters;

        $blogCriteria = new StatsCriteria(
            range: StatsRange::from($range),
            bloggerId: $bloggerId,
            blogId: $blogId,
            limit: $limit,
            sort: StatsSort::from($sort),
        );

        $blogs = $this->stats->blogViews($blogCriteria);

        $postFilters = $this->parseStatsFilters($request, 'posts_');
        [
            'range' => $postRange,
            'sort' => $postSort,
            'limit' => $postLimit,
            'blogger_id' => $postBloggerId,
            'blog_id' => $postBlogId
        ] = $postFilters;

        $posts = $this->getPostViews($postRange, $postLimit, $postSort, $postBloggerId, $postBlogId);

        $bloggers = User::query()
            ->where('role', User::ROLE_BLOGGER)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $blogOptions = Blog::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->when($bloggerId, fn($query) => $query->where('user_id', $bloggerId))
            ->get();

        $postBlogOptions = Blog::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->when($postBloggerId, fn($query) => $query->where('user_id', $postBloggerId))
            ->get();

        return Inertia::render('Admin/Stats', [
            'blogFilters' => $this->formatFiltersForResponse($blogFilters, $limit),
            'postFilters' => $this->formatFiltersForResponse($postFilters, $postLimit),
            'blogs' => $blogs,
            'posts' => $posts,
            'bloggers' => $bloggers,
            'blogOptions' => $blogOptions,
            'postBlogOptions' => $postBlogOptions,
        ]);
    }
}
