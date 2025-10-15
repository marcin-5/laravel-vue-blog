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
        $sitemapPath = public_path('sitemap.xml');
        $beforeHash = file_exists($sitemapPath) ? @sha1_file($sitemapPath) : null;

        try {
            $this->sitemapService->generate();

            $afterHash = file_exists($sitemapPath) ? @sha1_file($sitemapPath) : null;

            if ($afterHash && $afterHash !== $beforeHash) {
                Log::info('Sitemap regenerated', [
                    'changed' => true,
                    'path' => $sitemapPath,
                ]);
            } else {
                Log::info('Sitemap regenerate executed but no content change detected', [
                    'changed' => false,
                    'path' => $sitemapPath,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to regenerate sitemap: ' . $e->getMessage());
        }
    }
}
