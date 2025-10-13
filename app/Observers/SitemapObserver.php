<?php

namespace App\Observers;

use App\Services\SitemapService;
use Exception;
use Log;

class SitemapObserver
{
    public function __construct(protected SitemapService $sitemapService)
    {
    }

    /**
     * Regenerate the sitemap file.
     */
    public function regenerateSitemap(): void
    {
        try {
            $this->sitemapService->generate();
        } catch (Exception $e) {
            Log::error('Failed to regenerate sitemap: ' . $e->getMessage());
        }
    }
}
