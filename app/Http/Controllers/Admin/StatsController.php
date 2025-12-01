<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Controllers\Concerns\HandlesStatsFilters;
use App\Models\Blog;
use App\Models\User;
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
        $filters = $this->parseStatsFilters($request);
        ['range' => $range, 'sort' => $sort, 'limit' => $limit] = $filters;

        $bloggerId = $request->has('blogger_id') ? (int)$request->query('blogger_id') : null;
        $blogId = $request->has('blog_id') ? (int)$request->query('blog_id') : null;

        $blogs = $this->stats->blogViews($range, $bloggerId, $blogId, $limit, $sort);

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

        $posts = $blogId ? $this->getPostViews($blogId, $range, $limit, $sort) : [];

        return Inertia::render('Admin/Stats', [
            'filters' => array_merge(
                $this->formatFiltersForResponse($filters, $limit),
                [
                    'blogger_id' => $bloggerId,
                    'blog_id' => $blogId,
                ],
            ),
            'blogs' => $blogs,
            'posts' => $posts,
            'bloggers' => $bloggers,
            'blogOptions' => $blogOptions,
        ]);
    }
}
