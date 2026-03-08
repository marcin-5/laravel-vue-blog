<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;

class PostService
{
    public function createPost(Blog $blog, array $postData, ?int $userId = null): Post
    {
        return $blog->posts()->create(array_merge($postData, [
            'user_id' => $userId,
        ]));
    }

    public function updatePost(Post $post, array $postData): Post
    {
        $post->update($postData);

        return $post;
    }

    public function getUserPosts(int $userId, ?int $blogId = null): Builder
    {
        return Post::query()
            ->manageableBy($userId)
            ->with(['blog:id,name,slug,user_id', 'group:id,name,slug,user_id'])
            ->when($blogId, fn(Builder $q) => $q->where('blog_id', $blogId))
            ->orderByDesc('created_at');
    }
}
