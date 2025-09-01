<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoriesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display a listing of categories with blog counts (admin only).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', \App\Models\Category::class);

        $categories = Category::query()
            ->withCount('blogs')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Categories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Category::create([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Category created.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->name = $validated['name'];
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
