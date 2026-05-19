<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Models\Blog;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TagsController extends AuthenticatedController
{
    /**
     * List tags for a blog.
     */
    public function index(Blog $blog): JsonResponse
    {
        $this->authorize('update', $blog);

        return response()->json(
            $blog->tags()->orderBy('name')->get(['id', 'name', 'slug'])
        );
    }

    /**
     * Create a new tag for a blog.
     */
    public function store(Request $request, Blog $blog): JsonResponse
    {
        $this->authorize('update', $blog);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120'],
        ]);

        $slug = $data['slug'] ?? str($data['name'])->slug()->toString();
        $exists = Tag::query()->where('blog_id', $blog->id)->where('slug', $slug)->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'slug' => __('validation.unique', ['attribute' => 'slug']),
            ]);
        }

        $tag = $blog->tags()->create([
            'name' => $data['name'],
            'slug' => $slug,
        ]);

        return response()->json(['id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug], 201);
    }

    /**
     * Update an existing tag on a blog.
     */
    public function update(Request $request, Blog $blog, Tag $tag): JsonResponse
    {
        $this->authorize('update', $blog);
        abort_unless($tag->blog_id === $blog->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120'],
        ]);

        $slug = $data['slug'] ?? str($data['name'])->slug()->toString();
        $exists = Tag::query()
            ->where('blog_id', $blog->id)
            ->where('slug', $slug)
            ->whereKeyNot($tag->id)
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'slug' => __('validation.unique', ['attribute' => 'slug']),
            ]);
        }

        $tag->update([
            'name' => $data['name'],
            'slug' => $slug,
        ]);

        return response()->json(['id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug]);
    }

    /**
     * Delete a tag from a blog.
     */
    public function destroy(Blog $blog, Tag $tag): JsonResponse
    {
        $this->authorize('update', $blog);
        abort_unless($tag->blog_id === $blog->id, 404);

        $tag->delete();

        return response()->json(['status' => 'ok']);
    }
}
