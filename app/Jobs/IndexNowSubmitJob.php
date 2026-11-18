<?php

namespace App\Jobs;

use App\DTOs\IndexNowItem;
use App\Models\IndexNowQueuedUrl;
use App\Services\IndexNowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IndexNowSubmitJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(IndexNowService $indexNowService): void
    {
        $nextRun = Cache::get('index_now_next_run');

        if ($nextRun && now()->lt($nextRun)) {
            Log::info('IndexNowSubmitJob: Postponing submission. Latest update was less than 1 hour ago.');
            return;
        }

        $items = IndexNowQueuedUrl::query()->get()->map(fn(IndexNowQueuedUrl $queuedUrl) => new IndexNowItem(
            $queuedUrl->url,
            $queuedUrl->blog_id,
            $queuedUrl->post_id,
        ))->all();

        if (empty($items)) {
            Log::info('IndexNowSubmitJob: No URLs to submit.');
            return;
        }

        if ($indexNowService->submitUrls($items)) {
            IndexNowQueuedUrl::truncate();
            Log::info('IndexNowSubmitJob: Successfully submitted ' . count($items) . ' URLs.');
        } else {
            Log::error('IndexNowSubmitJob: Failed to submit URLs.');
        }
    }
}
