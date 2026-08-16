<?php

namespace App\Http\Controllers;

use App\Builders\PublicHomeSeoBuilder;
use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Http\Requests\UnsubscribeNewsletterRequest;
use App\Queries\Public\NewsletterQuery;
use App\Services\IdentityResolver;
use App\Services\NewsletterService;
use App\Services\TranslationService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Response;
use Throwable;

class NewsletterController extends BasePublicController
{
    public function __construct(
        protected TranslationService $translations,
        private readonly IdentityResolver $identityResolver,
        private readonly NewsletterService $newsletterService,
        private readonly PublicHomeSeoBuilder $seoBuilder,
        private readonly NewsletterQuery $newsletterQuery,
    ) {
        parent::__construct($translations);
    }

    /**
     * @throws FileNotFoundException
     */
    public function index(Request $request): Response
    {
        $pageData = $this->newsletterQuery->handleSubscribe($request);

        $messages = $this->translations->getPageTranslations('newsletter');

        return $this->renderWithTranslations('public/Newsletter', 'newsletter', [
            'blogs' => $pageData['blogs'],
            'selectedBlogId' => $pageData['selectedBlogId'],
            'userEmail' => $request->user()?->email,
            'mode' => 'subscribe',
            'config' => config('newsletter'),
            'seo' => $this->seoBuilder->buildNewsletterSeo($messages)->toArray(),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        $this->newsletterService->subscribe(
            $request->validated('email'),
            $request->validated('subscriptions'),
            $this->identityResolver->resolvedVisitorId($request),
        );

        return back()->with('message', __('newsletter.messages.success_subscribe'));
    }

    /**
     * @throws FileNotFoundException
     */
    public function manage(Request $request): Response
    {
        if (!$request->hasValidSignature()) {
            abort(403, __('newsletter.messages.invalid_signature'));
        }

        $email = $request->query('email');
        $pageData = $this->newsletterQuery->handleManage($email);

        $messages = $this->translations->getPageTranslations('newsletter');

        return $this->renderWithTranslations('public/Newsletter', 'newsletter', [
            'blogs' => $pageData['blogs'],
            'email' => $email,
            'currentSubscriptions' => $pageData['currentSubscriptions'],
            'updateUrl' => URL::signedRoute('newsletter.update', ['email' => $email]),
            'unsubscribeUrl' => URL::signedRoute('newsletter.unsubscribe', ['email' => $email]),
            'mode' => 'manage',
            'config' => config('newsletter'),
            'seo' => $this->seoBuilder->buildNewsletterSeo($messages)->toArray(),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, __('newsletter.messages.invalid_signature'));
        }

        $this->newsletterService->updateSubscriptions(
            $request->validated('email'),
            $request->validated('subscriptions'),
            $this->identityResolver->resolvedVisitorId($request),
        );

        return back()->with('message', __('newsletter.messages.success_manage'));
    }

    public function unsubscribe(UnsubscribeNewsletterRequest $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, __('newsletter.messages.invalid_signature'));
        }

        $this->newsletterService->unsubscribe($request->validated('email'));

        return redirect()->route('home')->with('message', __('newsletter.messages.unsubscribed'));
    }
}
