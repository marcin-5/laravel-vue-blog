<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogsController extends Controller
{
    /**
     * Display a listing of the authenticated user's blogs.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(!!$user, 403);

        $blogs = Blog::query()
            ->where('user_id', $user->id)
            ->with(['categories:id,name'])
            ->orderByDesc('created_at')
            ->get(['id', 'user_id', 'name', 'slug', 'description', 'is_published', 'created_at']);

        $categories = Category::query()
            ->orderBy('name')
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
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->isBlogger()), 403);

        // Enforce quota for bloggers (admins bypass)
        if (!$user->isAdmin()) {
            abort_unless($user->canCreateBlog(), 403, 'Blog quota reached.');
        }

        $name = trim((string)($request->input('name') ?: 'New Blog'));
        $base = Str::slug($name);
        $slug = $base ?: 'blog';
        $i = 1;
        while (Blog::where('slug', $slug)->exists()) {
            $slug = ($base ?: 'blog') . '-' . $i++;
        }

        $validated = $request->validate([
            'description' => ['nullable', 'string'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ]);

        $blog = Blog::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_published' => false,
        ]);

        if (!empty($validated['categories'])) {
            $blog->categories()->sync($validated['categories']);
        }

        return redirect()->route('blogs.index')->with('success', 'Blog created successfully.');
    }

    /**
     * Update the specified blog in storage.
     */
    public function update(Request $request, Blog $blog): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);
        abort_unless($user->isAdmin() || $blog->user_id === $user->id, 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ]);

        if (array_key_exists('name', $validated)) {
            $blog->name = $validated['name'];
            // Regenerate slug if name changed and slug not explicitly provided
            $base = Str::slug($validated['name']);
            $slug = $base ?: 'blog';
            $i = 1;
            while (Blog::where('slug', $slug)->where('id', '!=', $blog->id)->exists()) {
                $slug = ($base ?: 'blog') . '-' . $i++;
            }
            $blog->slug = $slug;
        }

        if (array_key_exists('description', $validated)) {
            $blog->description = $validated['description'];
        }

        if (array_key_exists('is_published', $validated)) {
            $blog->is_published = (bool)$validated['is_published'];
        }

        $blog->save();

        if (array_key_exists('categories', $validated)) {
            $blog->categories()->sync($validated['categories'] ?? []);
        }

        return back()->with('success', 'Blog updated successfully.');
    }
}
