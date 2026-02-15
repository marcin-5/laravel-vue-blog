<?php

namespace App\Jobs;

use App\Models\AnonymousView;
use App\Models\UserAgent;
use App\Services\UserAgentNormalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class StoreAnonymousView implements ShouldQueue
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
        $normalizer = new UserAgentNormalizer;
        $normalizedUa = $normalizer->normalize($this->data['user_agent'] ?? null);

        $userAgent = UserAgent::firstOrCreate(['name' => $normalizedUa]);

        AnonymousView::query()->upsert([
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
            'hits' => DB::raw('anonymous_views.hits + 1'),
            'last_seen_at',
            'updated_at',
        ]);
    }
}
