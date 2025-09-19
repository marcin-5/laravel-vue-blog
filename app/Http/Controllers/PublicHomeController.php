<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicHomeController extends Controller
{
    /**
     * Show the welcome page with blogs and categories filter.
     */
    public function welcome(Request $request): Response
    {
        $locale = app()->getLocale();

        // Read selected categories from query: supports CSV string or array
        $selected = $request->query('categories');
        if (is_string($selected)) {
            $selectedCategoryIds = collect(explode(',', $selected))
                ->map(fn($v) => (int)trim($v))
                ->filter()
                ->values()
                ->all();
        } elseif (is_array($selected)) {
            $selectedCategoryIds = collect($selected)
                ->map(fn($v) => (int)$v)
                ->filter()
                ->values()
                ->all();
        } else {
            $selectedCategoryIds = [];
        }

        // Load categories (localized name)
        $categories = Category::query()
            ->select(['id', 'slug', 'name'])
            ->orderBy('name->' . $locale)
            ->get()
            ->map(fn(Category $c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->getTranslation('name', $locale) ?? $c->slug,
            ])
            ->values();

        // Load blogs with categories; filter when categories selected
        $blogsQuery = Blog::query()
            ->where('is_published', true)
            ->with(['categories' => function ($q) use ($locale) {
                $q->select(['categories.id', 'categories.slug', 'categories.name']);
            }])
            ->select(['id', 'name', 'slug', 'description', 'locale']);

        if (!empty($selectedCategoryIds)) {
            $blogsQuery->whereHas('categories', function ($q) use ($selectedCategoryIds) {
                $q->whereIn('categories.id', $selectedCategoryIds);
            });
        }

        $blogs = $blogsQuery->orderBy('name')->get()->map(function (Blog $b) {
            $blogLocale = $b->locale ?: app()->getLocale();

            return [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'description' => $b->description,
                'categories' => $b->categories
                    ->filter(fn($c) => method_exists($c, 'hasTranslation') ? $c->hasTranslation('name', $blogLocale) : true)
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'slug' => $c->slug,
                        'name' => $c->getTranslation('name', $blogLocale) ?? $c->slug,
                    ])->values(),
            ];
        })->values();

        return Inertia::render('Welcome', [
            'locale' => $locale,
            'blogs' => $blogs,
            'categories' => $categories,
            'selectedCategoryIds' => $selectedCategoryIds,
        ]);
    }
}
