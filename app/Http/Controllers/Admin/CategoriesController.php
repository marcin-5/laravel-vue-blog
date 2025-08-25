<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoriesController extends Controller
{
    /**
     * Display a listing of categories with blog counts (admin only).
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user() && $request->user()->isAdmin(), 403);

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
        abort_unless($request->user() && $request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $base = Str::slug($validated['name']);
        $slug = $base ?: 'category';
        $i = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = ($base ?: 'category') . '-' . $i++;
        }

        Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return back()->with('success', 'Category created.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user() && $request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->name = $validated['name'];
        $base = Str::slug($validated['name']);
        $slug = $base ?: 'category';
        $i = 1;
        while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
            $slug = ($base ?: 'category') . '-' . $i++;
        }
        $category->slug = $slug;
        $category->save();

        return back()->with('success', 'Category updated.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user() && $request->user()->isAdmin(), 403);

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
