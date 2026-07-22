<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class BasePublicController extends Controller
{
    private const string REQUIRED_SEO_KEY = 'seo';

    private const string TRANSLATIONS_KEY = 'translations';

    public function __construct(protected TranslationService $translations) {}

    /**
     * Render an Inertia component injecting SSR translations that are
     * strictly scoped to public controllers.
     *
     * Allows providing preprocessed translations via $data['translations'].
     * If provided, its 'messages' will be used instead of refetching.
     * @throws FileNotFoundException
     */
    protected function renderWithTranslations(string $component, string $pageType, array $data = []): Response
    {
        $this->ensureSeoPayloadExists($data);

        $providedTranslations = $data[self::TRANSLATIONS_KEY] ?? null;
        unset($data[self::TRANSLATIONS_KEY]);

        return Inertia::render(
            $component,
            array_merge($data, [
                self::TRANSLATIONS_KEY => $this->buildTranslationsPayload($pageType, $providedTranslations),
            ]),
        );
    }

    /**
     * Ensure every public page receives SEO data for SSR rendering.
     *
     * @param  array<string, mixed>  $data
     */
    private function ensureSeoPayloadExists(array $data): void
    {
        if (!isset($data[self::REQUIRED_SEO_KEY])) {
            throw new RuntimeException("The 'seo' key is required for public pages in " . static::class);
        }
    }

    /**
     * Build a consistent translations payload for public Inertia pages.
     *
     * @param  array<string, mixed>|mixed|null  $providedTranslations
     * @return array<string, mixed>
     * @throws FileNotFoundException
     */
    private function buildTranslationsPayload(string $pageType, mixed $providedTranslations): array
    {
        $providedTranslations = is_array($providedTranslations) ? $providedTranslations : [];
        $messages = $providedTranslations['messages'] ?? $this->translations->getPageTranslations($pageType);

        return array_merge([
            'locale' => app()->getLocale(),
            'messages' => $messages,
        ], $providedTranslations);
    }
}
