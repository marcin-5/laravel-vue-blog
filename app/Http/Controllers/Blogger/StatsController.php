<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Controllers\Concerns\HandlesStatsFilters;
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
        $statsData = $this->getStatsData($request, $user->id);

        return Inertia::render('app/blogger/Stats', array_merge($statsData, [
            'blogOptions' => $this->getBlogOptions($user->id),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('stats'),
            ],
        ]));
    }
}
