<?php

namespace App\Http\Controllers\Blogger;

use App\Enums\UserRole;
use App\Http\Controllers\AuthenticatedController;
use App\Http\Controllers\Concerns\HandlesStatsFilters;
use App\Models\User;
use App\Services\StatsService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends AuthenticatedController
{
    use HandlesStatsFilters;

    public function __construct(
        private readonly StatsService $stats,
        protected TranslationService $translations,
    ) {
        parent::__construct();
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();
        $statsData = $this->getStatsData($request, $isAdmin ? null : $user->id);

        $bloggers = $isAdmin ? User::query()
            ->whereIn('role', [UserRole::Admin->value, UserRole::Blogger->value])
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get() : null;

        return Inertia::render('app/blogger/Stats', array_merge($statsData, [
            'bloggers' => $bloggers,
            'blogOptions' => $this->getBlogOptions($isAdmin ? $statsData['blogFilters']['blogger_id'] : $user->id),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('stats'),
            ],
        ]));
    }
}
