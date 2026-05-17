<?php

declare(strict_types=1);

namespace App\Queries\Dashboard;

use App\Models\BotView;
use App\Models\PageView;
use App\Models\UserAgent;
use App\Services\BotDetector;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;

readonly class BotStatsQuery
{
    public function __construct(
        private BotDetector $botDetector,
    ) {}

    /**
     * Get bot and user agent statistics.
     *
     * @return array{
     *   userAgentStats: array{
     *     last_unique: Collection<int, array{id: int, name: string}>,
     *     last_added: Collection<int, UserAgent>
     *   },
     *   botStats: array{
     *     last_seen: Collection<int, array{id: int, name: string, matched_fragment: string, hits: int, last_seen_at: string}>,
     *     top_hits: Collection<int, array{id: int, name: string, matched_fragment: string, hits: int, last_seen_at: string}>,
     *     total_hits: int
     *   }
     * }
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
        return BotView::query()
            ->aggregatedByUserAgent()
            ->orderByDesc('last_seen_at')
            ->with('userAgent:id,name')
            ->limit(5)
            ->get()
            ->map($this->mapBotViewToResponse(...));
    }

    private function getMostActiveBots(): Collection
    {
        return BotView::query()
            ->aggregatedByUserAgent()
            ->orderByDesc('total_hits')
            ->with('userAgent:id,name')
            ->limit(5)
            ->get()
            ->map($this->mapBotViewToResponse(...));
    }

    /**
     * @return array{id: int, name: string, matched_fragment: string, hits: int, last_seen_at: string}
     */
    private function mapBotViewToResponse(BotView $botView): array
    {
        $userAgentName = $botView->userAgent->name;
        $matchedFragment = $this->botDetector->getBotName($userAgentName);
        $lastSeenAt = $botView->getAttribute('last_seen_at');
        $lastSeenAtIso = $lastSeenAt instanceof DateTimeInterface
            ? $lastSeenAt->format(DATE_ATOM)
            : Carbon::parse((string) $lastSeenAt)->toIso8601String();

        return [
            'id' => $botView->userAgent->id,
            'name' => $userAgentName,
            'matched_fragment' => $matchedFragment ?? $userAgentName,
            'hits' => (int) $botView->getAttribute('total_hits'),
            'last_seen_at' => $lastSeenAtIso,
        ];
    }
}
