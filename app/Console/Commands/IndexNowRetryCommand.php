<?php

namespace App\Console\Commands;

use App\Services\IndexNowFailureRetryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('blog:indexnow-retry {uuid : Failed IndexNow job UUID}')]
#[Description('Retry one failed IndexNow submission job')]
class IndexNowRetryCommand extends Command
{
    public function handle(IndexNowFailureRetryService $retryService): int
    {
        $uuid = (string) $this->argument('uuid');

        if (!$retryService->retry($uuid)) {
            $this->error("Failed IndexNow job [{$uuid}] was not found or is not an IndexNow submission.");

            return self::FAILURE;
        }

        $this->info("IndexNow submission job [{$uuid}] queued for retry.");

        return self::SUCCESS;
    }
}
