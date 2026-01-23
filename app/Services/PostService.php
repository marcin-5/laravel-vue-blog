<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Post;

class PostService
{
    public function createPost(Blog $blog, array $postData, ?int $userId = null): Post
    {
        $post = new Post($postData);

        $post->blog_id = $blog->id;
        $post->user_id = $userId;

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
            ->where(function ($q) use ($userId) {
                $q->whereHas('blog', function ($bq) use ($userId) {
                    $bq->where('user_id', $userId);
                })->orWhereHas('group', function ($gq) use ($userId) {
                    $gq->where('user_id', $userId);
                });
            })
            ->with(['blog:id,name,slug,user_id', 'group:id,name,slug,user_id'])
            ->orderByDesc('created_at');

        if ($blogId) {
            $query->where('blog_id', $blogId);
        }

        return $query;
    }

    public function canUserManagePost($userId, Post $post): bool
    {
        if ($post->blog && $post->blog->user_id === $userId) {
            return true;
        }

        if ($post->group && $post->group->user_id === $userId) {
            return true;
        }

        return false;
    }
}
