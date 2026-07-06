<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SitemapObserver
{
    /**
     * Clear sitemap cache.
     */
    public function regenerateSitemap(mixed $model = null): void
    {
        $locales = config('app.supported_locales', [config('app.locale')]);

        foreach ($locales as $locale) {
            Cache::forget("sitemap_main_{$locale}");
            Cache::forget("sitemap_{$locale}"); // Compatibility with old key
        }

        if ($model instanceof \App\Models\Blog) {
            Cache::forget("sitemap_blog_{$model->id}");
        }

        if ($model instanceof \App\Models\Post && $model->blog_id) {
            Cache::forget("sitemap_blog_{$model->blog_id}");
        }

        if ($model instanceof \App\Models\Tag && $model->blog_id) {
            Cache::forget("sitemap_blog_{$model->blog_id}");
        }

        Log::info('Sitemap cache cleared.');
    }
}
