<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Services\IdentityResolver;
use App\Services\TranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
