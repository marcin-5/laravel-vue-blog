<?php

namespace App\Jobs;

use App\Models\PageView;
use App\Models\UserAgent;
use App\Models\VisitorLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Redis;

use function sprintf;

class StorePageView implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly array $data,
    ) {
    }

    public function handle(): void
    {
        // Resolve effective identity: prefer user_id; if absent but visitor maps to a user, use that user_id.
        $data = $this->data;
        $userId = $data['user_id'] ?? null;
        $visitorId = $data['visitor_id'] ?? null;

        if ($userId === null && $visitorId) {
            $link = VisitorLink::query()->where('visitor_id', $visitorId)->first();
            if ($link !== null) {
                $userId = $link->user_id;
                $data['user_id'] = $userId;
            }
        }

        // Handle unique UserAgent
        if (isset($data['user_agent']) && $data['user_agent'] !== '') {
            $userAgent = UserAgent::firstOrCreate(['name' => $data['user_agent']]);
            $data['user_agent_id'] = $userAgent->id;
        }

        // Check if a unique row already exists for this identity and viewable.
        $existsQuery = PageView::query()
            ->where('viewable_type', $data['viewable_type'])
            ->where('viewable_id', $data['viewable_id']);

        if ($userId !== null) {
            $existsQuery->where('user_id', $userId);
        } else {
            $existsQuery->whereNull('user_id')->where('visitor_id', $visitorId);
        }

        if ($existsQuery->exists()) {
            return; // do not double count unique view
        }

        $pageView = PageView::create($data);

        if (!class_exists(Redis::class)) {
            return;
        }

        // counter update in Redis
        $key = sprintf(
            'page_views:count:%s:%d',
            $pageView->viewable_type,
            $pageView->viewable_id,
        );

        Cache::store('redis')->increment($key);
    }
}
