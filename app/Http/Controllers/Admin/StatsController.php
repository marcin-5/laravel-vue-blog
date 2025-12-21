<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Controllers\Concerns\HandlesStatsFilters;
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
        $blogFilters = $this->parseStatsFilters($request);
        $blogCriteria = $this->createCriteria($blogFilters);
        $blogs = $this->stats->blogViews($blogCriteria);

        $postFilters = $this->parseStatsFilters($request, 'posts_');
        $postCriteria = $this->createCriteria($postFilters);
        $posts = $this->stats->postViews($postCriteria);

        $visitorFilters = $this->parseStatsFilters($request, 'visitors_');
        $visitorCriteria = $this->createCriteria($visitorFilters);
        $visitors = $this->stats->visitorViews($visitorCriteria);

        $bloggers = User::query()
            ->where('role', User::ROLE_BLOGGER)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('app/admin/Stats', [
            'blogFilters' => $this->formatFiltersForResponse($blogFilters, $blogFilters['limit']),
            'postFilters' => $this->formatFiltersForResponse($postFilters, $postFilters['limit']),
            'blogs' => $blogs,
            'posts' => $posts,
            'visitorFilters' => $this->formatFiltersForResponse($visitorFilters, $visitorFilters['limit']),
            'visitors' => $visitors,
            'bloggers' => $bloggers,
            'blogOptions' => $this->getBlogOptions($blogFilters['blogger_id']),
            'postBlogOptions' => $this->getBlogOptions($postFilters['blogger_id']),
            'visitorBlogOptions' => $this->getBlogOptions(),
        ]);
    }
}
