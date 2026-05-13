<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SitemapObserver
{
    /**
     * Clear sitemap cache.
     */
    public function regenerateSitemap(): void
    {
        $locales = config('app.supported_locales', [config('app.locale')]);

        foreach ($locales as $locale) {
            Cache::forget("sitemap_{$locale}");
        }

        Log::info('Sitemap cache cleared for all supported locales.');
    }
}
