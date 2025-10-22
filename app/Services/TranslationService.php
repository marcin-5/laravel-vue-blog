<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class TranslationService
{
    /**
     * Return merged translation messages for a given page type.
     */
    public function getPageTranslations(string $pageType): array
    {
        $locale = app()->getLocale();

        $cacheTtl = (int) config('translations.cache_ttl', 0);
        $cacheKey = $cacheTtl > 0
            ? sprintf('page_translations:%s:%s', $locale, $pageType)
            : null;

        $loader = function () use ($locale, $pageType) {
            $messages = [];

            if (config('translations.include_root_json', true)) {
                $baseJson = resource_path("lang/{$locale}.json");
                if (File::exists($baseJson)) {
                    $base = json_decode(File::get($baseJson), true) ?: [];
                    if (is_array($base)) {
                        $messages = array_merge($messages, $base);
                    }
                }
            }

            $groups = (array) data_get(config('translations'), "page_groups.{$pageType}", []);

            foreach ($groups as $group) {
                $path = resource_path("lang/{$locale}/{$group}.json");
                if (!File::exists($path)) {
                    continue; // Skip missing groups
                }
                $groupMessages = json_decode(File::get($path), true) ?: [];
                if (is_array($groupMessages)) {
                    $messages = array_merge_recursive($messages, $groupMessages);
                }
            }

            return $messages;
        };

        if ($cacheKey) {
            return Cache::remember($cacheKey, $cacheTtl, $loader);
        }

        return $loader();
    }
}
