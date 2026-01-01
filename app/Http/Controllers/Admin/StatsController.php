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
        $statsData = $this->getStatsData($request);

        $bloggers = User::query()
            ->where('role', User::ROLE_BLOGGER)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('app/admin/Stats', array_merge($statsData, [
            'bloggers' => $bloggers,
            'blogOptions' => $this->getBlogOptions($statsData['blogFilters']['blogger_id']),
            'postBlogOptions' => $this->getBlogOptions($statsData['postFilters']['blogger_id']),
            'visitorBlogOptions' => $this->getBlogOptions(),
        ]));
    }
}
