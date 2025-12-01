<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Controllers\Concerns\HandlesStatsFilters;
use App\Models\Blog;
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
        $filters = $this->parseStatsFilters($request);
        ['range' => $range, 'sort' => $sort, 'limit' => $limit] = $filters;

        $blogId = $request->has('blog_id') ? (int)$request->query('blog_id') : null;

        // Stats for current blogger: blogs they own
        $blogs = $this->stats->blogViews($range, $user->id, $blogId, $limit, $sort);

        $blogOptions = Blog::query()
            ->where('user_id', $user->id)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $posts = [];
        if ($blogId && $this->userOwnsBlog($blogId, $user->id)) {
            $posts = $this->getPostViews($blogId, $range, $limit, $sort);
        }

        return Inertia::render('Blogger/Stats', [
            'filters' => array_merge(
                $this->formatFiltersForResponse($filters, $limit),
                ['blog_id' => $blogId],
            ),
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
