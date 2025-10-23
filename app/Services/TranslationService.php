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
        $cacheTtl = (int)config('translations.cache_ttl', 0);

        if ($cacheTtl <= 0) {
            return $this->loadAndMergeTranslations($locale, $pageType);
        }

        $cacheKey = sprintf('page_translations:%s:%s', $locale, $pageType);

        return Cache::remember(
            $cacheKey,
            $cacheTtl,
            fn() => $this->loadAndMergeTranslations($locale, $pageType),
        );
    }

    /**
     * Load and merge base and page-specific group translations.
     */
    private function loadAndMergeTranslations(string $locale, string $pageType): array
    {
        $messages = [];

        if (config('translations.include_root_json', true)) {
            $baseJsonPath = resource_path("lang/{$locale}.json");
            $messages = array_merge($messages, $this->loadTranslationFile($baseJsonPath));
        }

        $groups = (array)data_get(config('translations'), "page_groups.{$pageType}", []);

        foreach ($groups as $group) {
            $path = resource_path("lang/{$locale}/{$group}.json");
            $messages = $this->mergeAssociative($messages, $this->loadTranslationFile($path));
        }

        return $messages;
    }

    /**
     * Load a single JSON translation file.
     */
    private function loadTranslationFile(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?: [];
    }

    /**
     * Prefer array_replace_recursive to avoid nested arrays when keys collide.
     */
    private function mergeAssociative(array $base, array $override): array
    {
        if ($base === []) {
            return $override;
        }
        if ($override === []) {
            return $base;
        }

        return array_replace_recursive($base, $override);
    }
}
