<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Post;

class PostService
{
    public function createPost(Blog $blog, array $postData): Post
    {
        $post = new Post($postData);

        $post->blog_id = $blog->id;

        // Ensure slug is generated from title
        $post->slug = $post->title;

        // Handle publishing logic
        if ($post->is_published && !$post->published_at) {
            $post->published_at = now();
        }

        $post->save();

        return $post;
    }

    public function updatePost(Post $post, array $postData): Post
    {
        // Store the original publishing state
        $wasPublished = $post->is_published;

        // Update the post with new data
        $post->fill($postData);

        // Handle slug regeneration if title changed
        if (array_key_exists('title', $postData)) {
            $post->slug = $post->title;
        }

        // Handle publishing state changes
        if (array_key_exists('is_published', $postData)) {
            $isNowPublished = (bool)$postData['is_published'];

            // If post is being published for the first time, set published_at
            if ($isNowPublished && !$wasPublished && !$post->published_at) {
                $post->published_at = now();
            }
        }

        $post->save();

        return $post;
    }

    public function getUserPosts($userId, ?int $blogId = null)
    {
        $query = Post::query()
            ->whereHas('blog', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['blog:id,name,slug,user_id'])
            ->orderByDesc('created_at');

        if ($blogId) {
            $query->where('blog_id', $blogId);
        }

        return $query;
    }

    public function canUserManagePost($userId, Post $post): bool
    {
        return $post->blog->user_id === $userId;
    }
}
