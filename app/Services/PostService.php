<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Blog;
use App\Models\Group;
use App\Models\Tag;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

readonly class PostService
{
    public function createPost(?Blog $blog, array $postData, ?int $userId = null, ?Group $group = null): Post
    {
        $relatedPosts = $postData['related_posts'] ?? [];
        $externalLinks = $postData['external_links'] ?? [];
        $tags = $postData['tags'] ?? [];
        unset($postData['related_posts'], $postData['external_links'], $postData['tags']);

        $data = array_merge($postData, ['user_id' => $userId]);

        $post = $group ? $group->posts()->create($data) : $blog->posts()->create($data);

        $this->syncRelations($post, $relatedPosts, $externalLinks);
        if (!empty($tags)) {
            $this->syncTags($post, $tags, $blog ?? $post->blog);
        }

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
        $tags = $postData['tags'] ?? null;
        unset($postData['related_posts'], $postData['external_links'], $postData['tags']);

        $post->update($postData);

        if ($relatedPosts !== null || $externalLinks !== null) {
            $this->syncRelations($post, $relatedPosts ?? [], $externalLinks ?? []);
        }

        if ($tags !== null) {
            $this->syncTags($post, $tags, $post->blog);
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

    /**
     * Get available extensions for a post.
     *
     * @return Collection<int, Post>
     */
    public function getAvailableExtensions(Post $post): Collection
    {
        $attachedIds = $post->extensions()->pluck('extension_post_id');

        $query = Post::query()
            ->extensionType()
            ->whereNotIn('id', $attachedIds)
            ->where('id', '!=', $post->id)
            ->select(['id', 'title', 'excerpt']);

        if ($post->group_id) {
            $query->where('group_id', $post->group_id);
        } else {
            $query->where('blog_id', $post->blog_id);
        }

        return $query->get();
    }

    /**
     * Attach an extension to a post.
     */
    public function attachExtension(Post $post, int $extensionId, int $displayOrder = 0): void
    {
        $post->extensions()->syncWithoutDetaching([
            $extensionId => [
                'display_order' => $displayOrder,
            ],
        ]);
    }

    /**
     * Detach an extension from a post.
     */
    public function detachExtension(Post $post, int $extensionId): void
    {
        $post->extensions()->detach($extensionId);
    }

    /**
     * Reorder extensions for a post.
     *
     * @param  array<int, array{id: int, display_order: int}>  $extensions
     */
    public function reorderExtensions(Post $post, array $extensions): void
    {
        foreach ($extensions as $extension) {
            $post->extensions()->updateExistingPivot($extension['id'], [
                'display_order' => $extension['display_order'],
            ]);
        }
    }

    /**
     * Sync tags for a post ensuring tags exist for the given blog.
     *
     * @param  array<int, string|int|array{name?:string,slug?:string,id?:int}>  $tags
     */
    private function syncTags(Post $post, array $tags, Blog $blog): void
    {
        // Normalize incoming tags to tag IDs
        $ids = [];
        foreach ($tags as $tag) {
            $id = null;
            if (is_int($tag)) {
                $id = $tag;
            } elseif (is_string($tag)) {
                // Only use existing tag by slug within the same blog; do not auto-create here
                $existing = Tag::where('blog_id', $blog->id)
                    ->where('slug', str($tag)->slug()->toString())
                    ->first();
                $id = $existing?->id;
            } elseif (is_array($tag)) {
                if (isset($tag['id'])) {
                    $id = (int) $tag['id'];
                } elseif (isset($tag['slug']) || isset($tag['name'])) {
                    $name = (string) ($tag['name'] ?? $tag['slug']);
                    $existing = Tag::where('blog_id', $blog->id)
                        ->where('slug', str($name)->slug()->toString())
                        ->first();
                    $id = $existing?->id;
                }
            }

            if ($id !== null) {
                // Guard: ensure tag belongs to same blog
                $belongs = Tag::whereKey($id)->where('blog_id', $blog->id)->exists();
                if ($belongs) {
                    $ids[] = $id;
                }
            }
        }

        $post->tags()->sync(array_unique($ids));
    }
}
