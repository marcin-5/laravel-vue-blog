<?php

namespace App\Http\Controllers;

use App\Queries\Dashboard\BlogStatsQuery;
use App\Queries\Dashboard\BotStatsQuery;
use App\Queries\Dashboard\NewsletterStatsQuery;
use App\Queries\Dashboard\PostStatsQuery;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TranslationService $translations,
        protected NewsletterStatsQuery $newsletterQuery,
        protected BotStatsQuery $botQuery,
        protected BlogStatsQuery $blogQuery,
        protected PostStatsQuery $postQuery,
    ) {}

    /**
     * Handle the incoming request.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('app/Dashboard', [
            'newsletterSubscriptions' => Inertia::defer(fn() => $user->can('view_admin_stats') ? $this->newsletterQuery->handle() : []),
            'blogStats' => Inertia::defer(fn() => $user->can('view_blogs') ? $this->blogQuery->handle($user) : []),
            'postsStats' => Inertia::defer(fn() => $user->can('view_blogs') ? $this->postQuery->handle($user) : []),
            'userAgentStats' => Inertia::defer(fn() => $user->can('view_admin_stats') ? $this->botQuery->handle()['userAgentStats'] : null),
            'botStats' => Inertia::defer(fn() => $user->can('view_admin_stats') ? $this->botQuery->handle()['botStats'] : null),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('dashboard'),
            ],
        ]);
    }
}
