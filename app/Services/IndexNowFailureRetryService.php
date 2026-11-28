<?php

namespace App\Services;

use App\Jobs\IndexNowSubmitJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class IndexNowFailureRetryService
{
    public function retry(string $uuid): bool
    {
        $failedJob = DB::table('failed_jobs')
            ->select(['payload'])
            ->where('uuid', $uuid)
            ->first();

        if (!$failedJob) {
            return false;
        }

        $payload = json_decode($failedJob->payload, true);

        if (!is_array($payload) || ($payload['data']['commandName'] ?? null) !== IndexNowSubmitJob::class) {
            return false;
        }

        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return true;
    }
}
