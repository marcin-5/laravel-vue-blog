<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\File;

trait LoadsTranslations
{
    /**
     * Load translation messages for the given locale from resource lang JSON files.
     * Includes the base {locale}.json and whitelisted namespaces.
     */
    private function loadTranslations(string $locale, array $namespaces = []): array
    {
        $messages = [];

        // Base JSON file: resources/lang/{locale}.json
        $baseJson = resource_path("lang/{$locale}.json");
        if (File::exists($baseJson)) {
            $base = json_decode(File::get($baseJson), true) ?: [];
            if (is_array($base)) {
                $messages = array_merge($messages, $base);
            }
        }

        // Namespaced files: resources/lang/{locale}/{ns}.json
        foreach ($namespaces as $ns) {
            $nsPath = resource_path("lang/{$locale}/{$ns}.json");
            if (File::exists($nsPath)) {
                $nsMsgs = json_decode(File::get($nsPath), true) ?: [];
                if (is_array($nsMsgs)) {
                    $messages = array_merge_recursive($messages, $nsMsgs);
                }
            }
        }

        return $messages;
    }
}
