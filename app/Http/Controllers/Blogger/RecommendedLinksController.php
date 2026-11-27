<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\Blogger\ReorderRecommendedLinksRequest;
use App\Http\Requests\Blogger\StoreRecommendedLinkRequest;
use App\Http\Requests\Blogger\UpdateRecommendedLinkRequest;
use App\Models\Blog;
use App\Models\RecommendedLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class RecommendedLinksController extends AuthenticatedController
{
    public function index(Blog $blog): Response
    {
        $this->authorize('update', $blog);

        return Inertia::render('app/blogger/RecommendedLinks', [
            'blog' => $blog->only(['id', 'name']),
            'categories' => $blog->recommendedLinkCategories()->get(['id', 'name', 'display_order']),
            'recommendedLinks' => $blog->recommendedLinks()->get(
                ['id', 'url', 'name', 'description', 'category_id', 'display_order'],
            ),
        ]);
    }

    public function store(StoreRecommendedLinkRequest $request, Blog $blog): RedirectResponse
    {
        $validated = $request->validated();
        $categoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
        $validated['category_id'] = $categoryId;
        $sectionQuery = $blog->recommendedLinks();
        if ($categoryId === null) {
            $sectionQuery->whereNull('category_id');
        } else {
            $sectionQuery->where('category_id', $categoryId);
        }

        $validated['display_order'] = ($sectionQuery->max('display_order') ?? -1) + 1;
        $blog->recommendedLinks()->create($validated);
        $this->clearNavigationCache($blog);

        return back()->with('success', __('blogger.recommended_links.created'));
    }

    private function clearNavigationCache(Blog $blog): void
    {
        Cache::tags(["navigation:{$blog->getMorphClass()}:$blog->id"])->flush();
    }

    /**
     * @throws Throwable
     */
    public function reorder(ReorderRecommendedLinksRequest $request, Blog $blog): RedirectResponse
    {
        $categoryId = $request->validated('category_id');
        $linkIds = array_map('intval', $request->validated('link_ids'));
        $categoryId = $categoryId === null ? null : (int) $categoryId;

        DB::transaction(static function () use ($blog, $categoryId, $linkIds): void {
            $sectionQuery = $blog->recommendedLinks();
            if ($categoryId === null) {
                $sectionQuery->whereNull('category_id');
            } else {
                $sectionQuery->where('category_id', $categoryId);
            }

            $links = $sectionQuery->get()->keyBy('id');
            if ($links->count() !== count($linkIds) || array_diff($linkIds, $links->keys()->all()) !== []) {
                throw ValidationException::withMessages([
                    'link_ids' => __('blogger.recommended_links.invalid_order'),
                ]);
            }

            foreach ($linkIds as $displayOrder => $linkId) {
                $link = $links->get($linkId);
                if (!$link || !$link->update(['display_order' => $displayOrder])) {
                    throw new RuntimeException('Unable to save recommended link order.');
                }
            }
        });

        $this->clearNavigationCache($blog);

        return redirect()
            ->route('blogger.recommended-links.index', $blog)
            ->with('success', __('blogger.recommended_links.reordered'));
    }

    public function update(
        UpdateRecommendedLinkRequest $request,
        Blog $blog,
        RecommendedLink $recommendedLink,
    ): RedirectResponse {
        $validated = $request->validated();

        if (array_key_exists('category_id', $validated)) {
            $categoryId = $validated['category_id'] === null ? null : (int) $validated['category_id'];
            $validated['category_id'] = $categoryId;

            if ($categoryId !== $recommendedLink->category_id) {
                $sectionQuery = $blog->recommendedLinks()->where('id', '!=', $recommendedLink->id);
                if ($categoryId === null) {
                    $sectionQuery->whereNull('category_id');
                } else {
                    $sectionQuery->where('category_id', $categoryId);
                }

                $validated['display_order'] = ($sectionQuery->max('display_order') ?? -1) + 1;
            }
        }

        $recommendedLink->update($validated);
        $this->clearNavigationCache($blog);

        return back()->with('success', __('blogger.recommended_links.updated'));
    }

    public function destroy(Blog $blog, RecommendedLink $recommendedLink): RedirectResponse
    {
        $this->authorize('update', $blog);
        $recommendedLink->delete();
        $this->clearNavigationCache($blog);

        return back()->with('success', __('blogger.recommended_links.deleted'));
    }
}
