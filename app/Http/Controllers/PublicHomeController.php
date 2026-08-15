<?php

namespace App\Http\Controllers;

use App\Actions\SubmitContactFormAction;
use App\Builders\PublicHomeSeoBuilder;
use App\Http\Requests\ContactSubmitRequest;
use App\Queries\Public\WelcomeQuery;
use App\Services\MarkdownService;
use App\Services\SloganSelector;
use App\Services\TranslationService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Inertia\Response;

class PublicHomeController extends BasePublicController
{
    public function __construct(
        private readonly MarkdownService $markdown,
        private readonly PublicHomeSeoBuilder $seoBuilder,
        private readonly SubmitContactFormAction $submitAction,
        private readonly SloganSelector $slogans,
        protected TranslationService $translations,
    ) {
        parent::__construct($translations);
    }

    /**
     * Show the welcome page with blogs and categories filter.
     * @throws FileNotFoundException
     */
    public function welcome(Request $request, WelcomeQuery $query): Response
    {
        $data = $query->handle($request);

        $messages = $this->translations->getPageTranslations('home');

        return $this->renderWithTranslations('public/Welcome', 'home', array_merge($data, [
            'seo' => $this->seoBuilder->buildWelcomeSeo(
                $data['blogs'],
                $messages,
                $data['selectedCategoryIds'],
            )->toArray(),
            'displayedSlogan' => $this->slogans->select(data_get($messages, 'slogans')),
            'translations' => ['messages' => $messages],
        ]));
    }

    /**
     * About page (SSR) — stays scoped to public controllers only.
     * If you also need `about` group messages, you can augment them here without
     * changing the service mapping, or keep the original logic if preferred.
     * @throws FileNotFoundException
     */
    public function about(): Response
    {
        $locale = app()->getLocale();
        $messages = $this->translations->getPageTranslations('about');

        // Convert about.content markdown to HTML if present
        if ($aboutContent = data_get($messages, 'about.content')) {
            data_set($messages, 'about.content', $this->markdown->convertToHtml($aboutContent));
        }

        return $this->renderWithTranslations('public/About', 'about', [
            'locale' => $locale,
            'aboutHeading' => data_get($messages, 'about.heading'),
            'aboutHtml' => data_get($messages, 'about.content'),
            'translations' => ['messages' => $messages],
            'seo' => $this->seoBuilder->buildAboutSeo($messages)->toArray(),
        ]);
    }

    /**
     * Contact page (SSR).
     * @throws FileNotFoundException
     */
    public function contact(): Response
    {
        $messages = $this->translations->getPageTranslations('contact');

        return $this->renderWithTranslations('public/Contact', 'contact', [
            'locale' => app()->getLocale(),
            'seo' => $this->seoBuilder->buildContactSeo($messages)->toArray(),
            'recipientName' => (string) config('admin.name'),
        ]);
    }

    public function submit(ContactSubmitRequest $request)
    {
        $this->submitAction->execute($request->validated());

        return response()->json([
            'message' => 'Thanks for your message. We will get back to you soon.',
        ]);
    }
}
