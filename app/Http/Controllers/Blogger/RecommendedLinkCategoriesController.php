<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\Blogger\ReorderRecommendedLinkCategoriesRequest;
use App\Http\Requests\Blogger\StoreRecommendedLinkCategoryRequest;
use App\Http\Requests\Blogger\UpdateRecommendedLinkCategoryRequest;
use App\Models\Blog;
use App\Models\RecommendedLinkCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class RecommendedLinkCategoriesController extends AuthenticatedController
{
    public function index(Blog $blog): Response
    {
        $this->authorize('update', $blog);

        return Inertia::render('app/blogger/RecommendedLinkCategories', [
            'blog' => $blog->only(['id', 'name']),
            'categories' => $blog->recommendedLinkCategories()->get(['id', 'name', 'display_order']),
        ]);
    }

    public function store(StoreRecommendedLinkCategoryRequest $request, Blog $blog): RedirectResponse
    {
        $this->authorize('update', $blog);

        $lastDisplayOrder = $blog->recommendedLinkCategories()->max('display_order');

        $category = $blog->recommendedLinkCategories()->make($request->validated());
        $category->display_order = ($lastDisplayOrder ?? -1) + 1;
        $category->save();

        return back()->with('success', __('blogger.recommended_link_categories.created'));
    }

    public function update(
        UpdateRecommendedLinkCategoryRequest $request,
        Blog $blog,
        RecommendedLinkCategory $recommendedLinkCategory,
    ): RedirectResponse {
        $this->authorize('update', $blog);
        $recommendedLinkCategory->update($request->validated());

        return back()->with('success', __('blogger.recommended_link_categories.updated'));
    }

    public function destroy(Blog $blog, RecommendedLinkCategory $recommendedLinkCategory): RedirectResponse
    {
        $this->authorize('update', $blog);
        $recommendedLinkCategory->delete();

        return back()->with('success', __('blogger.recommended_link_categories.deleted'));
    }

    /**
     * @throws Throwable
     */
    public function reorder(ReorderRecommendedLinkCategoriesRequest $request, Blog $blog): JsonResponse
    {
        $this->authorize('update', $blog);

        $categoryIds = $request->validated('category_ids');

        DB::transaction(static function () use ($blog, $categoryIds): void {
            $categories = $blog->recommendedLinkCategories()->get()->keyBy('id');

            if ($categories->count() !== count($categoryIds)) {
                throw ValidationException::withMessages([
                    'category_ids' => __('blogger.recommended_link_categories.invalid_order'),
                ]);
            }

            foreach ($categoryIds as $displayOrder => $categoryId) {
                $category = $categories->get($categoryId);

                if (!$category || !$category->update(['display_order' => $displayOrder])) {
                    throw new RuntimeException('Unable to save recommended link category order.');
                }
            }
        });

        return response()->json(['success' => true]);
    }
}
