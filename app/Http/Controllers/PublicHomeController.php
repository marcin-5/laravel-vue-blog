<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsTranslations;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use ParsedownExtra;

class PublicHomeController extends Controller
{
    use LoadsTranslations;

    /**
     * Show the welcome page with blogs and categories filter.
     */
    public function welcome(Request $request): Response
    {
        $locale = app()->getLocale();

        // Read selected categories from query: supports CSV string or array
        $selected = $request->query('categories');
        if (is_string($selected)) {
            $selectedCategoryIds = collect(explode(',', $selected))
                ->map(fn($v) => (int)trim($v))
                ->filter()
                ->values()
                ->all();
        } elseif (is_array($selected)) {
            $selectedCategoryIds = collect($selected)
                ->map(fn($v) => (int)$v)
                ->filter()
                ->values()
                ->all();
        } else {
            $selectedCategoryIds = [];
        }

        // Load categories (localized name) with Redis cache
        $cacheKey = "welcome_categories_{$locale}";
        $categories = Cache::remember($cacheKey, 3600, function () use ($locale) {
            return Category::query()
                ->select(['id', 'slug', 'name'])
                ->orderBy('name->' . $locale)
                ->get()
                ->map(fn(Category $c) => [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'name' => $c->getTranslation('name', $locale) ?? $c->slug,
                ])
                ->values();
        });

        // Load blogs with categories; filter when categories selected - with Redis cache
        $categoryFilter = empty($selectedCategoryIds) ? 'all' : implode(',', $selectedCategoryIds);
        $blogsCacheKey = "welcome_blogs_{$locale}_{$categoryFilter}";

        $blogs = Cache::remember($blogsCacheKey, 3600, function () use ($selectedCategoryIds, $locale) {
            $blogsQuery = Blog::query()
                ->where('is_published', true)
                ->with([
                    'categories' => function ($q) use ($locale) {
                        $q->select(['categories.id', 'categories.slug', 'categories.name']);
                    },
                    'user' => function ($q) {
                        $q->select(['id', 'name']);
                    }
                ])
                ->select(['id', 'name', 'slug', 'description', 'locale', 'user_id']);

            if (!empty($selectedCategoryIds)) {
                $blogsQuery->whereHas('categories', function ($q) use ($selectedCategoryIds) {
                    $q->whereIn('categories.id', $selectedCategoryIds);
                });
            }

            return $blogsQuery->orderBy('name')->get()->map(function (Blog $b) {
                $blogLocale = $b->locale ?: app()->getLocale();

                // Parse markdown description to safe HTML
                $descriptionHtml = '';
                if (!empty($b->description)) {
                    $parser = new ParsedownExtra();
                    if (method_exists($parser, 'setSafeMode')) {
                        $parser->setSafeMode(true);
                    }
                    $descriptionHtml = $parser->text($b->description);
                }

                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'slug' => $b->slug,
                    'author' => $b->user?->name ?? '',
                    'descriptionHtml' => $descriptionHtml,
                    'categories' => $b->categories
                        ->filter(
                            fn($c) => method_exists($c, 'hasTranslation') ? $c->hasTranslation(
                                'name',
                                $blogLocale,
                            ) : true,
                        )
                        ->map(fn($c) => [
                            'id' => $c->id,
                            'slug' => $c->slug,
                            'name' => $c->getTranslation('name', $blogLocale) ?? $c->slug,
                        ])->values(),
                ];
            })->values();
        });

        $baseUrl = config('app.url');
        $canonicalUrl = $baseUrl . (empty($selectedCategoryIds) ? '' : '?categories=' . implode(
                    ',',
                    $selectedCategoryIds,
                ));
        $ogImage = $baseUrl . '/og-image.png';

        // Get translated SEO text
        $translations = $this->loadTranslations($locale, ['landing']);
        $seoTitle = $translations['landing']['meta']['welcomeTitle'] ?? config('app.name');
        $seoDescription = $translations['landing']['meta']['welcomeDescription'] ?? 'Welcome to ' . config('app.name');

        return Inertia::render('Welcome', [
            'locale' => $locale,
            'blogs' => $blogs,
            'categories' => $categories,
            'selectedCategoryIds' => $selectedCategoryIds,
            // SEO meta data for SSR
            'seo' => [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'canonicalUrl' => $canonicalUrl,
                'ogImage' => $ogImage,
                'ogType' => 'website',
                'locale' => $locale,
                'structuredData' => $this->generateHomeStructuredData($blogs, $seoTitle, $seoDescription, $baseUrl),
            ],
            // Provide translations to avoid async loading flicker on SSR
            'translations' => [
                'locale' => $locale,
                'messages' => $translations,
            ],
        ]);
    }

    /**
     * Generate structured data for home page.
     */
    private function generateHomeStructuredData($blogs, string $title, string $description, string $baseUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => $title,
            'url' => $baseUrl,
            'description' => $description,
            'blogPost' => collect($blogs)->slice(0, 10)->map(function ($blog) use ($baseUrl) {
                return [
                    '@type' => 'BlogPosting',
                    'headline' => $blog['name'],
                    'author' => [
                        '@type' => 'Person',
                        'name' => $blog['author'],
                    ],
                    'url' => $baseUrl . '/blogs/' . $blog['slug'],
                    'description' => $blog['descriptionHtml'] ? strip_tags($blog['descriptionHtml']) : null,
                ];
            })->all(),
        ];
    }

}
