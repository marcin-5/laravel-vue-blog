<?php

namespace App\Jobs;

use App\Models\PageView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

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
        $pageView = PageView::create($this->data);

        // counter update in Redis
        $key = sprintf(
            'page_views:count:%s:%d',
            $pageView->viewable_type,
            $pageView->viewable_id,
        );

        Cache::store('redis')->increment($key);
    }
}
