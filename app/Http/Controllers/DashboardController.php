<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $dashboardData = $this->prepareDashboardData($user);

        return Inertia::render('app/Dashboard', [
            ...$dashboardData,
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('dashboard'),
            ],
        ]);
    }

    /**
     * Prepare data for the dashboard based on user permissions.
     */
    private function prepareDashboardData(User $user): array
    {
        $data = [
            'newsletterSubscriptions' => [],
            'blogStats' => [],
            'postsStats' => [],
            'userAgentStats' => null,
            'botStats' => null,
        ];

        if ($user->can('view_admin_stats')) {
            $data['newsletterSubscriptions'] = $this->newsletterQuery->handle();
            $botStats = $this->botQuery->handle();
            $data['userAgentStats'] = $botStats['userAgentStats'];
            $data['botStats'] = $botStats['botStats'];
        }

        if ($user->can('view_blogs')) {
            $data['blogStats'] = $this->blogQuery->handle($user);
            $data['postsStats'] = $this->postQuery->handle($user);
        }

        return $data;
    }
}
