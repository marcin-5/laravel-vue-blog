<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Controllers\Concerns\HandlesStatsFilters;
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
        $blogCriteria = $this->createCriteria($blogFilters, $user->id);
        $blogs = $this->stats->blogViews($blogCriteria);

        $postFilters = $this->parseStatsFilters($request, 'posts_');
        $postCriteria = $this->createCriteria($postFilters, $user->id);
        $posts = $this->stats->postViews($postCriteria);

        $visitorFilters = $this->parseStatsFilters($request, 'visitors_');
        $visitorCriteria = $this->createCriteria($visitorFilters, $user->id);
        $visitors = $this->stats->visitorViews($visitorCriteria);

        return Inertia::render('Blogger/Stats', [
            'blogFilters' => $this->formatFiltersForResponse($blogFilters, $blogFilters['limit']),
            'postFilters' => $this->formatFiltersForResponse($postFilters, $postFilters['limit']),
            'visitorFilters' => $this->formatFiltersForResponse($visitorFilters, $visitorFilters['limit']),
            'blogs' => $blogs,
            'posts' => $posts,
            'visitors' => $visitors,
            'blogOptions' => $this->getBlogOptions($user->id),
        ]);
    }
}
