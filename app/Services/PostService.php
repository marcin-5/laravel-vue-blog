<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;

class PostService
{
    public function createPost(?Blog $blog, array $postData, ?int $userId = null, ?Group $group = null): Post
    {
        $relatedPosts = $postData['related_posts'] ?? [];
        $externalLinks = $postData['external_links'] ?? [];
        unset($postData['related_posts'], $postData['external_links']);

        $data = array_merge($postData, ['user_id' => $userId]);

        $post = $group ? $group->posts()->create($data) : $blog->posts()->create($data);

        $this->syncRelations($post, $relatedPosts, $externalLinks);

        return $post;
    }

    private function syncRelations(Post $post, array $relatedPosts, array $externalLinks): void
    {
        if (!empty($relatedPosts) || $post->relatedPosts()->exists()) {
            $post->relatedPosts()->delete();
            foreach ($relatedPosts as $index => $rp) {
                $post->relatedPosts()->create([
                    'blog_id' => $rp['blog_id'],
                    'related_post_id' => $rp['related_post_id'],
                    'reason' => $rp['reason'] ?? null,
                    'display_order' => $rp['display_order'] ?? $index,
                ]);
            }
        }

        if (!empty($externalLinks) || $post->externalLinks()->exists()) {
            $post->externalLinks()->delete();
            foreach ($externalLinks as $index => $el) {
                $post->externalLinks()->create([
                    'title' => $el['title'],
                    'url' => $el['url'],
                    'description' => $el['description'] ?? null,
                    'reason' => $el['reason'] ?? null,
                    'display_order' => $el['display_order'] ?? $index,
                ]);
            }
        }
    }

    public function updatePost(Post $post, array $postData): Post
    {
        $relatedPosts = $postData['related_posts'] ?? null;
        $externalLinks = $postData['external_links'] ?? null;
        unset($postData['related_posts'], $postData['external_links']);

        $post->update($postData);

        if ($relatedPosts !== null || $externalLinks !== null) {
            $this->syncRelations($post, $relatedPosts ?? [], $externalLinks ?? []);
        }

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
