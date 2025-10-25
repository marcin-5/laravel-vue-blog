<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\Blog;
use App\Models\Category;
use App\Services\MarkdownService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Response;

class PublicHomeController extends BasePublicController
{
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

                // Parse markdown description to HTML (sanitized via HTML Purifier in MarkdownService)
                $descriptionHtml = '';
                if (!empty($b->description)) {
                    /** @var MarkdownService $md */
                    $md = app(MarkdownService::class);
                    $descriptionHtml = $md->convertToHtml($b->description);
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

        // Pull messages for SEO from the service (home page type)
        $messages = $this->translations->getPageTranslations('home');
        $seoTitle = data_get($messages, 'meta.welcomeTitle') ?? config('app.name');
        $seoDescription = data_get($messages, 'meta.welcomeDescription') ?? ('Welcome to ' . config(
                'app.name',
            ));

        return $this->renderWithTranslations('Welcome', 'home', [
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

    /**
     * About page (SSR) — stays scoped to public controllers only.
     * If you also need `about` group messages, you can augment them here without
     * changing the service mapping, or keep the original logic if preferred.
     */
    public function about(MarkdownService $markdown): Response
    {
        $locale = app()->getLocale();

        // Start with about page messages from the service
        $messages = $this->translations->getPageTranslations('about');

        // Convert about.content markdown to HTML if present
        $aboutContent = data_get($messages, 'about.content');
        if (is_string($aboutContent) && $aboutContent !== '') {
            $html = $markdown->convertToHtml($aboutContent);
            data_set($messages, 'about.content', $html);
        }

        $baseUrl = config('app.url');
        $seoTitle = data_get($messages, 'about.meta.title') ?? 'About';
        $seoDescription = data_get($messages, 'about.meta.description') ?? 'About this site';
        $canonicalUrl = rtrim($baseUrl, '/') . '/about';
        $ogImage = rtrim($baseUrl, '/') . '/og-image.png';

        return $this->renderWithTranslations('About', 'about', [
            'locale' => $locale,
            // Pass preprocessed translations (about.content already converted to HTML)
            'translations' => [
                'messages' => $messages,
            ],
            'seo' => [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'canonicalUrl' => $canonicalUrl,
                'ogImage' => $ogImage,
                'ogType' => 'website',
                'locale' => $locale,
                'structuredData' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'AboutPage',
                    'name' => $seoTitle,
                    'url' => $canonicalUrl,
                    'description' => $seoDescription,
                ],
            ],
            // Optionally expose pre-rendered about messages if your front-end expects them under a specific prop
            // 'aboutMessages' => data_get($messages, 'about'),
        ]);
    }

    /**
     * Contact page (SSR).
     */
    public function contact(): Response
    {
        $locale = app()->getLocale();

        $messages = $this->translations->getPageTranslations('contact');

        $baseUrl = config('app.url');
        $seoTitle = data_get($messages, 'contact.meta.title') ?? 'Contact';
        $seoDescription = data_get($messages, 'contact.meta.description') ?? 'Get in touch';
        $canonicalUrl = rtrim($baseUrl, '/') . '/contact';
        $ogImage = rtrim($baseUrl, '/') . '/og-image.png';

        return $this->renderWithTranslations('Contact', 'contact', [
            'locale' => $locale,
            'translations' => [
                'messages' => $messages,
            ],
            'seo' => [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'canonicalUrl' => $canonicalUrl,
                'ogImage' => $ogImage,
                'ogType' => 'website',
                'locale' => $locale,
                'structuredData' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'ContactPage',
                    'name' => $seoTitle,
                    'url' => $canonicalUrl,
                    'description' => $seoDescription,
                ],
            ],
        ]);
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Send the email (synchronous). If you want to queue, see notes below.
        Mail::to(config('mail.contact_to'))
            ->send(new ContactMessageMail($data));

        return response()->json([
            'message' => 'Thanks for your message. We will get back to you soon.'
        ]);
    }
}
