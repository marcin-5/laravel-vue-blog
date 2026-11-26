<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\Blogger\StoreRecommendedLinkRequest;
use App\Http\Requests\Blogger\UpdateRecommendedLinkRequest;
use App\Models\Blog;
use App\Models\RecommendedLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class RecommendedLinksController extends AuthenticatedController
{
    public function index(Blog $blog): Response
    {
        $this->authorize('update', $blog);

        return Inertia::render('app/blogger/RecommendedLinks', [
            'blog' => $blog->only(['id', 'name']),
            'recommendedLinks' => $blog->recommendedLinks()->get(['id', 'url', 'name', 'description']),
        ]);
    }

    public function store(StoreRecommendedLinkRequest $request, Blog $blog): RedirectResponse
    {
        $blog->recommendedLinks()->create($request->validated());
        $this->clearNavigationCache($blog);

        return back()->with('success', __('blogger.recommended_links.created'));
    }

    public function update(UpdateRecommendedLinkRequest $request, Blog $blog, RecommendedLink $recommendedLink): RedirectResponse
    {
        $recommendedLink->update($request->validated());

        return back()->with('success', __('blogger.recommended_links.updated'));
    }

    public function destroy(Blog $blog, RecommendedLink $recommendedLink): RedirectResponse
    {
        $this->authorize('update', $blog);
        $recommendedLink->delete();
        $this->clearNavigationCache($blog);

        return back()->with('success', __('blogger.recommended_links.deleted'));
    }

    private function clearNavigationCache(Blog $blog): void
    {
        Cache::tags(["navigation:{$blog->getMorphClass()}:$blog->id"])->flush();
    }
}
