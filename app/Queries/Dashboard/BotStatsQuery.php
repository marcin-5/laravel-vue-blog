<?php

namespace App\Queries\Dashboard;

use App\Models\BotView;
use App\Models\PageView;
use App\Models\UserAgent;
use App\Services\UserAgentNormalizer;
use Illuminate\Support\Collection;

class BotStatsQuery
{
    /**
     * Get bot and user agent statistics.
     *
     * @return array
     */
    public function handle(): array
    {
        return [
            'userAgentStats' => [
                'last_unique' => $this->getLastUniqueUserAgents(),
                'last_added' => $this->getLastAddedUserAgents(),
            ],
            'botStats' => [
                'last_seen' => $this->getLastBotViews(),
                'top_hits' => $this->getMostActiveBots(),
                'total_hits' => (int) BotView::query()->sum('hits'),
            ],
        ];
    }

    private function getLastUniqueUserAgents(): Collection
    {
        return PageView::query()
            ->select('user_agent_id')
            ->whereNotNull('user_agent_id')
            ->groupBy('user_agent_id')
            ->orderByRaw('MAX(created_at) DESC')
            ->with('userAgent:id,name')
            ->limit(5)
            ->get()
            ->map(fn($pageView) => [
                'id' => $pageView->userAgent->id,
                'name' => $pageView->userAgent->name,
            ]);
    }

    private function getLastAddedUserAgents(): Collection
    {
        return UserAgent::query()
            ->select(['id', 'name'])
            ->latest()
            ->limit(5)
            ->get();
    }

    private function getLastBotViews(): Collection
    {
        $sortedFragments = UserAgentNormalizer::getSortedBotFragments();

        return BotView::query()
            ->with('userAgent:id,name')
            ->latest('last_seen_at')
            ->get()
            ->unique('user_agent_id')
            ->take(5)
            ->map(function (BotView $botView) use ($sortedFragments) {
                $userAgentName = $botView->userAgent->name;
                $matchedFragment = collect($sortedFragments)->first(
                    fn($f) => stripos($userAgentName, (string) $f) !== false,
                );

                return [
                    'id' => $botView->userAgent->id,
                    'name' => $userAgentName,
                    'matched_fragment' => $matchedFragment ?? $userAgentName,
                    'hits' => $botView->hits,
                    'last_seen_at' => $botView->last_seen_at->toIso8601String(),
                ];
            })
            ->values();
    }

    private function getMostActiveBots(): Collection
    {
        $sortedFragments = UserAgentNormalizer::getSortedBotFragments();

        return BotView::query()
            ->selectRaw('user_agent_id, SUM(hits) as total_hits, MAX(last_seen_at) as last_seen_at')
            ->groupBy('user_agent_id')
            ->orderByDesc('total_hits')
            ->with('userAgent:id,name')
            ->limit(5)
            ->get()
            ->map(function ($botView) use ($sortedFragments) {
                $userAgentName = $botView->userAgent->name;
                $matchedFragment = collect($sortedFragments)->first(
                    fn($f) => stripos($userAgentName, (string) $f) !== false,
                );

                return [
                    'id' => $botView->userAgent->id,
                    'name' => $userAgentName,
                    'matched_fragment' => $matchedFragment ?? $userAgentName,
                    'hits' => (int) $botView->total_hits,
                    'last_seen_at' => $botView->last_seen_at->toIso8601String(),
                ];
            });
    }
}
