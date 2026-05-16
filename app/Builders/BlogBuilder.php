<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Blog;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of Blog
 * @extends Builder<TModelClass>
 */
class BlogBuilder extends Builder
{
    /**
     * Scope: Blogs that are published.
     */
    public function published(): self
    {
        return $this->where('is_published', true);
    }

    /**
     * Scope: Blogs for a specific locale.
     */
    public function forLocale(string $locale): self
    {
        return $this->where('locale', $locale);
    }

    /**
     * Scope: Load posts for the index view with proper ordering and fields.
     */
    public function withPostsForIndex(): self
    {
        return $this->with([
            'posts' => function ($q) {
                /** @var PostBuilder $q */
                $q
                    ->orderByRaw('COALESCE(published_at, created_at) DESC')
                    ->with([
                        'extensions' => function ($eq) {
                            /** @var PostBuilder $eq */
                            $eq->oldest();
                        },
                        'relatedPosts.relatedPost',
                        'relatedPosts.blog',
                        'externalLinks',
                    ])
                    ->select(
                        'id',
                        'blog_id',
                        'group_id',
                        'seo_title',
                        'title',
                        'slug',
                        'excerpt',
                        'summary',
                        'content',
                        'is_published',
                        'visibility',
                        'published_at',
                        'created_at',
                    );
            },
        ]);
    }

    /**
     * Scope: Load categories with minimal fields.
     */
    public function withCategories(): self
    {
        return $this->with('categories:id,name');
    }

    /**
     * Scope: Order blogs by the latest published post date.
     */
    public function orderByLatestPost(): self
    {
        return $this
            ->addSelect([
                'latest_post_at' => Post::query()
                    ->selectRaw('COALESCE(MAX(COALESCE(published_at, created_at)), NULL)')
                    ->whereColumn('blog_id', 'blogs.id')
                    ->where('is_published', true),
            ])
            ->orderByDesc('latest_post_at')
            ->orderBy('name');
    }
}
