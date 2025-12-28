<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Services\IdentityResolver;
use App\Services\TranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Response;

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
        $blogs = Blog::query()
            ->where('is_published', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return $this->renderWithTranslations('public/Newsletter', 'newsletter', [
            'blogs' => $blogs,
            'selectedBlogId' => $selectedBlogId,
            'userEmail' => $request->user()?->email,
        ]);
    }

    public function store(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        $visitorId = $this->identityResolver->resolvedVisitorId($request);
        $data = $request->validated();

        foreach ($data['blog_ids'] as $blogId) {
            NewsletterSubscription::query()->updateOrCreate(
                [
                    'email' => $data['email'],
                    'blog_id' => $blogId,
                ],
                [
                    'frequency' => $data['frequency'],
                    'visitor_id' => $visitorId,
                ],
            );
        }

        return back()->with('message', 'Zapisano do newslettera pomyślnie!');
    }

    public function manage(Request $request): Response
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Link do zarządzania subskrypcją wygasł lub jest nieprawidłowy.');
        }

        $email = $request->query('email');
        $subscriptions = NewsletterSubscription::query()
            ->where('email', $email)
            ->get();

        $blogs = Blog::query()
            ->where('is_published', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return $this->renderWithTranslations('public/NewsletterManage', 'newsletter', [
            'blogs' => $blogs,
            'email' => $email,
            'currentSubscriptions' => $subscriptions->pluck('blog_id'),
            'frequency' => $subscriptions->first()?->frequency ?? 'daily',
            'updateUrl' => URL::signedRoute('newsletter.update', ['email' => $email]),
            'unsubscribeUrl' => URL::signedRoute('newsletter.unsubscribe', ['email' => $email]),
        ]);
    }

    public function update(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $data = $request->validated();
        $email = $data['email'];

        // Remove subscriptions not in the list
        NewsletterSubscription::query()
            ->where('email', $email)
            ->whereNotIn('blog_id', $data['blog_ids'])
            ->delete();

        $visitorId = $this->identityResolver->resolvedVisitorId($request);

        // Update or create subscriptions
        foreach ($data['blog_ids'] as $blogId) {
            NewsletterSubscription::query()->updateOrCreate(
                [
                    'email' => $email,
                    'blog_id' => $blogId,
                ],
                [
                    'frequency' => $data['frequency'],
                    'visitor_id' => $visitorId,
                ],
            );
        }

        return back()->with('message', 'Ustawienia newslettera zostały zaktualizowane.');
    }

    public function unsubscribe(Request $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $email = $request->input('email');
        NewsletterSubscription::query()->where('email', $email)->delete();

        return redirect()->route('home')->with('message', 'Zostałeś wypisany z newslettera.');
    }
}
