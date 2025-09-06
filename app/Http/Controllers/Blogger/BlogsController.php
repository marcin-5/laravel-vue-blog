<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        $this->authorizeResource(\App\Models\Blog::class, 'blog');
    }

    /**
     * Display a listing of the authenticated user's blogs.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', \App\Models\Blog::class);
        $user = $request->user();

        $blogs = Blog::query()
            ->where('user_id', $user->id)
            ->with([
                'categories:id,name',
                'posts' => function ($q) {
                    $q->orderByRaw('COALESCE(published_at, created_at) DESC')
                        ->select('id', 'blog_id', 'title', 'excerpt', 'content', 'is_published', 'visibility', 'published_at', 'created_at');
                },
            ])
            ->orderByDesc('created_at')
            ->get(['id', 'user_id', 'name', 'slug', 'description', 'is_published', 'locale', 'sidebar', 'page_size', 'created_at']);

        $categories = Category::query()
            ->orderBy('slug')
            ->get(['id', 'name']);

        return Inertia::render('Blogs', [
            'blogs' => $blogs,
            'categories' => $categories,
            'canCreate' => $user->canCreateBlog(),
        ]);
    }

    /**
     * Store a newly created blog in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Blog::class);
        $user = $request->user();

        $name = trim((string)($request->input('name') ?: 'New Blog'));

        $validated = $request->validate([
            'description' => ['nullable', 'string'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'locale' => ['sometimes', 'string', 'in:en,pl'],
            'sidebar' => ['sometimes', 'integer', 'between:-50,50'],
            'page_size' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $blog = Blog::create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => $validated['description'] ?? null,
            'is_published' => false,
            'locale' => $validated['locale'] ?? app()->getLocale() ?? 'en',
            'sidebar' => (int)($validated['sidebar'] ?? 0),
            'page_size' => (int)($validated['page_size'] ?? 10),
        ]);

        if (!empty($validated['categories'])) {
            $blog->categories()->sync($validated['categories']);
        }

        return redirect()->route('blogs.index')->with('success', __('blogs.messages.blog_created'));
    }

    /**
     * Update the specified blog in storage.
     */
    public function update(Request $request, Blog $blog): RedirectResponse
    {
        $this->authorize('update', $blog);
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'locale' => ['sometimes', 'string', 'in:en,pl'],
            'sidebar' => ['sometimes', 'integer', 'between:-50,50'],
            'page_size' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        if (array_key_exists('name', $validated)) {
            $blog->name = $validated['name'];
            // Slug will be auto-adjusted by observer if name changed
        }

        if (array_key_exists('description', $validated)) {
            $blog->description = $validated['description'];
        }

        if (array_key_exists('is_published', $validated)) {
            $blog->is_published = (bool)$validated['is_published'];
        }
        if (array_key_exists('locale', $validated)) {
            $blog->locale = $validated['locale'];
        }
        if (array_key_exists('sidebar', $validated)) {
            $blog->sidebar = (int)$validated['sidebar'];
        }
        if (array_key_exists('page_size', $validated)) {
            $blog->page_size = (int)$validated['page_size'];
        }

        $blog->save();

        if (array_key_exists('categories', $validated)) {
            $blog->categories()->sync($validated['categories'] ?? []);
        }

        return back()->with('success', __('blogs.messages.blog_updated'));
    }
}
