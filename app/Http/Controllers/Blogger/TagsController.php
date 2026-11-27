<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\Blogger\DestroyTagRequest;
use App\Http\Requests\Blogger\StoreTagRequest;
use App\Http\Requests\Blogger\UpdateTagLandingPageRequest;
use App\Http\Requests\Blogger\UpdateTagRequest;
use App\Models\Blog;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TagsController extends AuthenticatedController
{
    public function index(Blog $blog): Response
    {
        $this->authorize('update', $blog);

        return Inertia::render('app/blogger/Tags', [
            'blog' => $blog->only(['id', 'name']),
            'tags' => $blog->tags()
                ->with('landingPage')
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn(Tag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'landing_page_content' => $tag->landingPage?->content ?? '',
                ])
                ->values(),
        ]);
    }

    public function options(Blog $blog): JsonResponse
    {
        $this->authorize('update', $blog);

        return response()->json($blog->tags()->orderBy('name')->get(['id', 'name', 'slug']));
    }

    public function updateLandingPage(UpdateTagLandingPageRequest $request, Blog $blog, Tag $tag): RedirectResponse
    {
        $landingPage = $tag->landingPage()->firstOrNew();
        $landingPage->content = $request->validated('content') ?? '';
        $landingPage->save();

        return redirect()
            ->route('blogger.tags.index', $blog)
            ->with('success', __('blogger.tags.landing_page_saved'));
    }

    public function store(StoreTagRequest $request, Blog $blog): RedirectResponse
    {
        $blog->tags()->create($request->safe()->only(['name', 'slug']));

        return redirect()
            ->route('blogger.tags.index', $blog)
            ->with('success', __('blogger.tags.created'));
    }

    public function update(UpdateTagRequest $request, Blog $blog, Tag $tag): RedirectResponse
    {
        $tag->update($request->safe()->only(['name', 'slug']));

        return redirect()
            ->route('blogger.tags.index', $blog)
            ->with('success', __('blogger.tags.updated'));
    }

    public function destroy(DestroyTagRequest $request, Blog $blog, Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()
            ->route('blogger.tags.index', $blog)
            ->with('success', __('blogger.tags.deleted'));
    }
}
