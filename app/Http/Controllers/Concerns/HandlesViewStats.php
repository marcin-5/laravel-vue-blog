<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AnonymousView;
use App\Models\BotView;
use App\Models\PageView;

trait HandlesViewStats
{
    /**
     * Get view statistics for a given viewable model.
     */
    protected function getViewStats(
        string $viewableType,
        int $viewableId,
        int $ownerId,
        bool $onlyConsented = false,
    ): ?array {
        $user = auth()->user();

        if (!$user || !($user->isAdmin() || $user->id === $ownerId)) {
            return null;
        }

        $consented = PageView::where('viewable_type', $viewableType)
            ->where('viewable_id', $viewableId)
            ->count();

        if ($onlyConsented) {
            return [
                'consented' => (int)$consented,
                'anonymous' => 0,
                'bots' => 0,
            ];
        }

        $anonymous = AnonymousView::where('viewable_type', $viewableType)
            ->where('viewable_id', $viewableId)
            ->sum('hits');

        $bots = BotView::where('viewable_type', $viewableType)
            ->where('viewable_id', $viewableId)
            ->sum('hits');

        return [
            'consented' => (int)$consented,
            'anonymous' => (int)($anonymous ?: 0),
            'bots' => (int)($bots ?: 0),
        ];
    }
}
