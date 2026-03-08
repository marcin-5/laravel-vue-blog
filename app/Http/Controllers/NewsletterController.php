<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Services\IdentityResolver;
use App\Services\TranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Inertia\Response;
use LaravelIdea\Helper\App\Models\_IH_Blog_C;

class NewsletterController extends BasePublicController
{
    public function __construct(
        protected TranslationService $translations,
        private readonly IdentityResolver $identityResolver,
    ) {
        parent::__construct($translations);
    }

    public function index(Request $request): Response
    {
        $selectedBlogId = $request->integer('blog_id');
        $blogs = $this->getPublishedBlogs();

        $this->setLocaleFromBlog($blogs, $selectedBlogId);

        return $this->renderWithTranslations('public/Newsletter', 'newsletter', [
            'blogs' => $blogs,
            'selectedBlogId' => $selectedBlogId,
            'userEmail' => $request->user()?->email,
            'mode' => 'subscribe',
            'config' => config('newsletter'),
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

    public function store(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        $this->processSubscriptions($request);

        return back()->with('message', __('newsletter.messages.success_subscribe'));
    }

    private function processSubscriptions(StoreNewsletterSubscriptionRequest $request): void
    {
        $visitorId = $this->identityResolver->resolvedVisitorId($request);
        $data = $request->validated();

        foreach ($data['subscriptions'] as $sub) {
            NewsletterSubscription::query()->updateOrCreate(
                [
                    'email' => $data['email'],
                    'blog_id' => $sub['blog_id'],
                ],
                [
                    'frequency' => $sub['frequency'],
                    'send_time' => $sub['send_time'] ?? null,
                    'send_time_weekend' => $sub['send_time_weekend'] ?? null,
                    'send_day' => $sub['send_day'] ?? null,
                    'visitor_id' => $visitorId,
                ],
            );
        }
    }

    public function manage(Request $request): Response
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Link do zarządzania subskrypcją wygasł lub jest nieprawidłowy.');
        }

        $email = $request->query('email');
        $subscriptions = NewsletterSubscription::query()
            ->where('email', $email)
            ->with('blog:id,locale')
            ->get();

        $this->setLocaleFromSubscriptions($subscriptions);

        $blogs = $this->getPublishedBlogs();

        return $this->renderWithTranslations('public/Newsletter', 'newsletter', [
            'blogs' => $blogs,
            'email' => $email,
            'currentSubscriptions' => $this->mapSubscriptionsToArray($subscriptions),
            'updateUrl' => URL::signedRoute('newsletter.update', ['email' => $email]),
            'unsubscribeUrl' => URL::signedRoute('newsletter.unsubscribe', ['email' => $email]),
            'mode' => 'manage',
            'config' => config('newsletter'),
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

    public function update(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $this->removeUnselectedSubscriptions($request);
        $this->processSubscriptions($request);

        return back()->with('message', __('newsletter.messages.success_manage'));
    }

    private function removeUnselectedSubscriptions(StoreNewsletterSubscriptionRequest $request): void
    {
        $data = $request->validated();
        $email = $data['email'];
        $blogIds = collect($data['subscriptions'])->pluck('blog_id');

        NewsletterSubscription::query()
            ->where('email', $email)
            ->whereNotIn('blog_id', $blogIds)
            ->delete();
    }

    public function unsubscribe(Request $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $email = $request->input('email');
        NewsletterSubscription::query()->where('email', $email)->delete();

        return redirect()->route('home')->with('message', __('newsletter.messages.unsubscribed'));
    }
}
