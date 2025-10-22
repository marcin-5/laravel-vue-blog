<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Inertia\Inertia;
use Inertia\Response;

class BasePublicController extends Controller
{
    public function __construct(protected TranslationService $translations)
    {
    }

    /**
     * Render an Inertia component injecting SSR translations that are
     * strictly scoped to public controllers.
     *
     * Allows providing preprocessed translations via $data['translations'].
     * If provided, its 'messages' will be used instead of refetching.
     */
    protected function renderWithTranslations(string $component, string $pageType, array $data = []): Response
    {
        $locale = app()->getLocale();

        // Respect provided translations override, if any
        $provided = $data['translations'] ?? null;
        $messages = $provided['messages'] ?? $this->translations->getPageTranslations($pageType);

        // Ensure we pass a consistent translations payload
        $translationsPayload = array_merge([
            'locale' => $locale,
            'messages' => $messages,
        ], is_array($provided) ? $provided : []);

        // Avoid leaking raw 'translations' input further down
        if (isset($data['translations'])) {
            unset($data['translations']);
        }

        return Inertia::render($component, array_merge($data, [
            'translations' => $translationsPayload,
        ]));
    }
}
