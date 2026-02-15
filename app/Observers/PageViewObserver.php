<?php

namespace App\Observers;

use App\Models\PageView;
use App\Models\UserAgent;
use App\Services\UserAgentNormalizer;
use Throwable;

class PageViewObserver
{
    public function creating(PageView $view): void
    {
        $raw = $view->user_agent ?? '';

        try {
            $label = app(UserAgentNormalizer::class)->normalize($raw);
        } catch (Throwable $e) {
            $label = 'Unknown';
        }

        $ua = UserAgent::query()->firstOrCreate(['name' => $label]);
        $view->user_agent_id = $ua->id;
    }
}
