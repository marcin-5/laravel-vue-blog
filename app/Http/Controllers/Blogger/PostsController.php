<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'blog_id' => ['required', 'integer', 'exists:blogs,id'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        // Ensure the blog belongs to the current user
        $blog = Blog::query()->where('id', $validated['blog_id'])->where('user_id', $user->id)->firstOrFail();

        $isPublished = (bool)($validated['is_published'] ?? false);

        $post = new Post([
            'blog_id' => $blog->id,
            'title' => $validated['title'],
            // slug will be set by Post::setSlugAttribute from title
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'is_published' => $isPublished,
            'visibility' => Post::VIS_PUBLIC, // default to public for now
        ]);

        // Ensure slug is generated from title before saving (mutator will slugify)
        $post->slug = $post->title;

        if ($isPublished && empty($post->published_at)) {
            $post->published_at = now();
        }

        $post->save();

        return back()->with('success', 'Post created successfully.');
    }

    /**
     * Update an existing post.
     */
    public function update(Request $request, Post $post): RedirectResponse
    {
        $user = $request->user();

        // Ensure the post belongs to a blog owned by the current user
        $blog = Blog::query()->where('id', $post->blog_id)->where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('title', $validated)) {
            $post->title = $validated['title'];
            // trigger slug regeneration
            $post->slug = $post->title;
        }
        if (array_key_exists('excerpt', $validated)) {
            $post->excerpt = $validated['excerpt'];
        }
        if (array_key_exists('content', $validated)) {
            $post->content = $validated['content'];
        }
        if (array_key_exists('is_published', $validated)) {
            $post->is_published = (bool)$validated['is_published'];
            if ($post->is_published && empty($post->published_at)) {
                $post->published_at = now();
            }
        }

        $post->save();

        return back()->with('success', 'Post updated successfully.');
    }
}
