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
            'mode' => 'subscribe',
            'config' => config('newsletter'),
        ]);
    }

    public function store(StoreNewsletterSubscriptionRequest $request): RedirectResponse
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
                    'send_day' => $sub['send_day'] ?? null,
                    'visitor_id' => $visitorId,
                ],
            );
        }

        return back()->with('message', 'Zapisano do newslettera pomyślnie!');
    }

    public function manage(Request $request): Response
    {
        if (!$request->hasValidSignature(false)) {
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

        return $this->renderWithTranslations('public/Newsletter', 'newsletter', [
            'blogs' => $blogs,
            'email' => $email,
            'currentSubscriptions' => $subscriptions->map(fn($s) => [
                'blog_id' => $s->blog_id,
                'frequency' => $s->frequency,
                'send_time' => $s->send_time,
                'send_day' => $s->send_day,
            ]),
            'updateUrl' => url(URL::signedRoute('newsletter.update', ['email' => $email], absolute: false)),
            'unsubscribeUrl' => url(URL::signedRoute('newsletter.unsubscribe', ['email' => $email], absolute: false)),
            'mode' => 'manage',
            'config' => config('newsletter'),
        ]);
    }

    public function update(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        if (!$request->hasValidSignature(false)) {
            abort(403);
        }

        $data = $request->validated();
        $email = $data['email'];

        $blogIds = collect($data['subscriptions'])->pluck('blog_id');

        // Remove subscriptions not in the list
        NewsletterSubscription::query()
            ->where('email', $email)
            ->whereNotIn('blog_id', $blogIds)
            ->delete();

        $visitorId = $this->identityResolver->resolvedVisitorId($request);

        // Update or create subscriptions
        foreach ($data['subscriptions'] as $sub) {
            NewsletterSubscription::query()->updateOrCreate(
                [
                    'email' => $email,
                    'blog_id' => $sub['blog_id'],
                ],
                [
                    'frequency' => $sub['frequency'],
                    'send_time' => $sub['send_time'] ?? null,
                    'send_day' => $sub['send_day'] ?? null,
                    'visitor_id' => $visitorId,
                ],
            );
        }

        return back()->with('message', 'Ustawienia newslettera zostały zaktualizowane.');
    }

    public function unsubscribe(Request $request): RedirectResponse
    {
        if (!$request->hasValidSignature(false)) {
            abort(403);
        }

        $email = $request->input('email');
        NewsletterSubscription::query()->where('email', $email)->delete();

        return redirect()->route('home')->with('message', 'Zostałeś wypisany z newslettera.');
    }
}
