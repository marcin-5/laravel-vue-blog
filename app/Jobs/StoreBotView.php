<?php

namespace App\Jobs;

use App\Models\BotView;
use App\Models\UserAgent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class StoreBotView implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly array $data,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $userAgent = UserAgent::firstOrCreate(['name' => $this->data['user_agent']]);

        BotView::query()->upsert([
            [
                'user_agent_id' => $userAgent->id,
                'viewable_type' => $this->data['viewable_type'],
                'viewable_id' => $this->data['viewable_id'],
                'hits' => 1,
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['user_agent_id', 'viewable_type', 'viewable_id'], [
            'hits' => DB::raw('bot_views.hits + 1'),
            'last_seen_at',
            'updated_at',
        ]);
    }
}
