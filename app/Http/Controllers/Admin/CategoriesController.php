<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Controllers\Concerns\ValidatesLocale;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoriesController extends AuthenticatedController
{
    use ValidatesLocale;

    /**
     * Display a listing of categories with blog counts (admin only).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->withCount('blogs')
            ->orderBy('slug')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('app/admin/Categories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $locale = $this->validateAndGetLocale($validated['locale'] ?? null);

        // Set the translated name for chosen locale
        Category::create([
            'name' => [$locale => $validated['name']],
        ]);

        return back()->with('success', 'Category created.');
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();

        $locale = $this->validateAndGetLocale($validated['locale'] ?? null);

        $category->setTranslation('name', $locale, $validated['name']);
        // Slug will be auto-adjusted by observer if name changed
        $category->save();

        return back()->with('success', 'Category updated.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
