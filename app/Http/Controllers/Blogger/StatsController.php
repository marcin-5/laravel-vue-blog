<?php

namespace App\Http\Controllers\Blogger;

use App\Enums\StatsRange;
use App\Enums\StatsSort;
use App\Http\Controllers\AuthenticatedController;
use App\Http\Controllers\Concerns\HandlesStatsFilters;
use App\Models\Blog;
use App\Services\StatsCriteria;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();

        $blogFilters = $this->parseStatsFilters($request);
        ['range' => $range, 'sort' => $sort, 'limit' => $limit, 'blog_id' => $blogId] = $blogFilters;

        $blogCriteria = new StatsCriteria(
            range: StatsRange::from($range),
            bloggerId: $user->id,
            blogId: $blogId,
            limit: $limit,
            sort: StatsSort::from($sort),
        );

        // Stats for current blogger: blogs they own
        $blogs = $this->stats->blogViews($blogCriteria);

        $postFilters = $this->parseStatsFilters($request, 'posts_');
        ['range' => $postRange, 'sort' => $postSort, 'limit' => $postLimit, 'blog_id' => $postBlogId] = $postFilters;

        $posts = $this->getPostViews($postRange, $postLimit, $postSort, $user->id, $postBlogId);

        $blogOptions = Blog::query()
            ->where('user_id', $user->id)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Blogger/Stats', [
            'blogFilters' => $this->formatFiltersForResponse($blogFilters, $limit),
            'postFilters' => $this->formatFiltersForResponse($postFilters, $postLimit),
            'blogs' => $blogs,
            'posts' => $posts,
            'blogOptions' => $blogOptions,
        ]);
    }

    private function userOwnsBlog(int $blogId, int $userId): bool
    {
        return Blog::query()
            ->where('id', $blogId)
            ->where('user_id', $userId)
            ->exists();
    }
}
