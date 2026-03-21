<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class PublicDataController extends Controller
{
    /**
     * Return a list of all published blogs.
     */
    public function blogs(): JsonResponse
    {
        $blogs = Blog::query()
            ->where('is_published', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json($blogs);
    }

    /**
     * Return a list of all published posts for the given blog.
     */
    public function posts(Blog $blog): JsonResponse
    {
        $posts = Post::query()
            ->where('blog_id', $blog->id)
            ->where('is_published', true)
            ->where('visibility', '!=', 'extension')
            ->orderBy('title')
            ->get(['id', 'title']);

        return response()->json($posts);
    }
}
