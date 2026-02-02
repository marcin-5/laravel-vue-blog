<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\SeoData;
use App\Http\Requests\ContactSubmitRequest;
use App\Mail\ContactMessageMail;
use App\Queries\Public\WelcomeQuery;
use App\Services\MarkdownService;
use App\Services\SeoService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Response;

class PublicHomeController extends BasePublicController
{
    public function __construct(
        private readonly MarkdownService $markdown,
        private readonly SeoService $seoService,
        protected TranslationService $translations,
    ) {
        parent::__construct($translations);
    }

    /**
     * Show the welcome page with blogs and categories filter.
     */
    public function welcome(Request $request, WelcomeQuery $query): Response
    {
        $data = $query->handle($request);
        $baseUrl = config('app.url');

        $messages = $this->translations->getPageTranslations('home');
        $seoTitle = data_get($messages, 'meta.welcomeTitle') ?? config('app.name');
        $seoDescription = data_get($messages, 'meta.welcomeDescription') ?? ('Welcome to ' . config('app.name'));

        $canonicalUrl = $baseUrl . (empty($data['selectedCategoryIds']) ? '' : '?categories=' . implode(
                    ',',
                    $data['selectedCategoryIds'],
                ));

        $alternateLinks = [
            ['hreflang' => 'pl', 'href' => $canonicalUrl],
            ['hreflang' => 'en', 'href' => $canonicalUrl],
            ['hreflang' => 'x-default', 'href' => $canonicalUrl],
        ];

        $seoData = new SeoData(
            title: $seoTitle,
            description: $seoDescription,
            canonicalUrl: $canonicalUrl,
            ogImage: $baseUrl . '/og-image.png',
            ogType: 'website',
            locale: $data['locale'],
            structuredData: $this->seoService->generateHomeStructuredData(
                $data['blogs']->toArray(),
                $seoTitle,
                $seoDescription,
                $baseUrl,
            ),
            alternateLinks: $alternateLinks,
        );

        return $this->renderWithTranslations('public/Welcome', 'home', array_merge($data, [
            'seo' => $seoData->toArray(),
        ]));
    }

    /**
     * About page (SSR) — stays scoped to public controllers only.
     * If you also need `about` group messages, you can augment them here without
     * changing the service mapping, or keep the original logic if preferred.
     */
    public function about(): Response
    {
        $locale = app()->getLocale();

        // Start with about page messages from the service
        $messages = $this->translations->getPageTranslations('about');

        // Convert about.content markdown to HTML if present
        $aboutContent = data_get($messages, 'about.content');
        if (is_string($aboutContent) && $aboutContent !== '') {
            $html = $this->markdown->convertToHtml($aboutContent);
            data_set($messages, 'about.content', $html);
        }

        $baseUrl = config('app.url');
        $seoTitle = data_get($messages, 'about.meta.title') ?? 'About';
        $seoDescription = data_get($messages, 'about.meta.description') ?? 'About this site';
        $canonicalUrl = rtrim($baseUrl, '/') . '/about';
        $ogImage = rtrim($baseUrl, '/') . '/og-image.png';

        $seoData = new SeoData(
            title: $seoTitle,
            description: $seoDescription,
            canonicalUrl: $canonicalUrl,
            ogImage: $ogImage,
            ogType: 'website',
            locale: $locale,
            structuredData: [
                '@context' => 'https://schema.org',
                '@type' => 'AboutPage',
                'name' => $seoTitle,
                'url' => $canonicalUrl,
                'description' => $seoDescription,
            ],
        );

        return $this->renderWithTranslations('public/About', 'about', [
            'locale' => $locale,
            // Pass preprocessed translations (about.content already converted to HTML)
            'translations' => [
                'messages' => $messages,
            ],
            'seo' => $seoData->toArray(),
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

        $seoData = new SeoData(
            title: $seoTitle,
            description: $seoDescription,
            canonicalUrl: $canonicalUrl,
            ogImage: $ogImage,
            ogType: 'website',
            locale: $locale,
            structuredData: [
                '@context' => 'https://schema.org',
                '@type' => 'ContactPage',
                'name' => $seoTitle,
                'url' => $canonicalUrl,
                'description' => $seoDescription,
            ],
        );

        return $this->renderWithTranslations('public/Contact', 'contact', [
            'locale' => $locale,
            'translations' => [
                'messages' => $messages,
            ],
            'seo' => $seoData->toArray(),
        ]);
    }

    public function submit(ContactSubmitRequest $request)
    {
        $data = $request->validated();

        // Send the email (synchronous). If you want to queue, see notes below.
        Mail::to(config('mail.contact_to'))
            ->send(new ContactMessageMail($data));

        return response()->json([
            'message' => 'Thanks for your message. We will get back to you soon.',
        ]);
    }
}
