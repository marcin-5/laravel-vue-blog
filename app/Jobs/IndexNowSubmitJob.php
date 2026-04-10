<?php

namespace App\Jobs;

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

        $urls = IndexNowQueuedUrl::pluck('url')->toArray();

        if (empty($urls)) {
            Log::info('IndexNowSubmitJob: No URLs to submit.');
            return;
        }

        if ($indexNowService->submitUrls($urls)) {
            IndexNowQueuedUrl::truncate();
            Log::info('IndexNowSubmitJob: Successfully submitted ' . count($urls) . ' URLs.');
        } else {
            Log::error('IndexNowSubmitJob: Failed to submit URLs.');
        }
    }
}
