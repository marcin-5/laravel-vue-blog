<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Inertia\Inertia;
use Inertia\Response;

class BasePublicController extends Controller
{
    public function __construct(protected TranslationService $translations) {}

    /**
     * Render an Inertia component injecting SSR translations that are
     * strictly scoped to public controllers.
     */
    protected function renderWithTranslations(string $component, string $pageType, array $data = []): Response
    {
        $locale = app()->getLocale();
        $messages = $this->translations->getPageTranslations($pageType);

        return Inertia::render($component, array_merge($data, [
            'translations' => [
                'locale' => $locale,
                'messages' => $messages,
            ],
        ]));
    }
}
