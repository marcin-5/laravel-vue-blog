<?php

namespace App\Http\Controllers;

use App\Builders\PublicHomeSeoBuilder;
use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Http\Requests\UnsubscribeNewsletterRequest;
use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Services\IdentityResolver;
use App\Services\NewsletterService;
use App\Services\TranslationService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Inertia\Response;
use LaravelIdea\Helper\App\Models\_IH_Blog_C;
use Throwable;

class NewsletterController extends BasePublicController
{
    public function __construct(
        protected TranslationService $translations,
        private readonly IdentityResolver $identityResolver,
        private readonly NewsletterService $newsletterService,
        private readonly PublicHomeSeoBuilder $seoBuilder,
    ) {
        parent::__construct($translations);
    }

    /**
     * @throws FileNotFoundException
     */
    public function index(Request $request): Response
    {
        $selectedBlogId = $request->integer('blog_id');
        $blogs = $this->getPublishedBlogs();

        $this->setLocaleFromBlog($blogs, $selectedBlogId);

        $messages = $this->translations->getPageTranslations('newsletter');

        return $this->renderWithTranslations('public/Newsletter', 'newsletter', [
            'blogs' => $blogs,
            'selectedBlogId' => $selectedBlogId,
            'userEmail' => $request->user()?->email,
            'mode' => 'subscribe',
            'config' => config('newsletter'),
            'seo' => $this->seoBuilder->buildNewsletterSeo($messages)->toArray(),
        ]);
    }

    private function getPublishedBlogs(): array|Collection|_IH_Blog_C
    {
        return Blog::query()
            ->where('is_published', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'locale']);
    }

    private function setLocaleFromBlog(Collection $blogs, ?int $blogId): void
    {
        if (!$blogId) {
            return;
        }

        $currentBlog = $blogs->firstWhere('id', $blogId);
        if ($currentBlog && $currentBlog->locale) {
            app()->setLocale($currentBlog->locale);
        }
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
        $subscriptions = NewsletterSubscription::query()
            ->where('email', $email)
            ->with('blog:id,locale')
            ->get();

        $this->setLocaleFromSubscriptions($subscriptions);

        $blogs = $this->getPublishedBlogs();

        $messages = $this->translations->getPageTranslations('newsletter');

        return $this->renderWithTranslations('public/Newsletter', 'newsletter', [
            'blogs' => $blogs,
            'email' => $email,
            'currentSubscriptions' => $this->mapSubscriptionsToArray($subscriptions),
            'updateUrl' => URL::signedRoute('newsletter.update', ['email' => $email]),
            'unsubscribeUrl' => URL::signedRoute('newsletter.unsubscribe', ['email' => $email]),
            'mode' => 'manage',
            'config' => config('newsletter'),
            'seo' => $this->seoBuilder->buildNewsletterSeo($messages)->toArray(),
        ]);
    }

    private function setLocaleFromSubscriptions(Collection $subscriptions): void
    {
        if ($subscriptions->isEmpty()) {
            return;
        }

        $firstSubBlog = $subscriptions->first()?->blog;
        if ($firstSubBlog && $firstSubBlog->locale) {
            app()->setLocale($firstSubBlog->locale);
        }
    }

    private function mapSubscriptionsToArray(Collection $subscriptions): Collection
    {
        return $subscriptions->map(fn($s) => [
            'blog_id' => $s->blog_id,
            'frequency' => $s->frequency,
            'send_time' => $s->send_time,
            'send_time_weekend' => $s->send_time_weekend,
            'send_day' => $s->send_day,
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
