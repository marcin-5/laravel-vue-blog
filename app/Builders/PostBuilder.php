<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Post;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of Post
 * @extends Builder<TModelClass>
 */
class PostBuilder extends Builder
{
    /**
     * Scope for extension posts
     */
    public function extensionType(): self
    {
        return $this->where('visibility', Post::VIS_EXTENSION);
    }

    /**
     * Scope for regular (non-extension) posts
     */
    public function regularPosts(): self
    {
        return $this->where('visibility', '!=', Post::VIS_EXTENSION);
    }

    /**
     * Scope to only published posts (published_at is set and not in the future)
     */
    public function published(): self
    {
        return $this
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to only public posts (visibility = 'public')
     */
    public function public(): self
    {
        return $this->where('visibility', Post::VIS_PUBLIC);
    }

    /**
     * Scope to order by publication date (published_at, then created_at)
     */
    public function orderByPublicationDate(string $direction = 'desc'): self
    {
        return $this
            ->orderBy('published_at', $direction)
            ->orderBy('created_at', $direction);
    }

    /**
     * Composite scope: published + public + ordered
     */
    public function forPublicView(): self
    {
        return $this
            ->published()
            ->whereIn('visibility', [Post::VIS_PUBLIC, Post::VIS_UNLISTED])
            ->orderByPublicationDate();
    }

    /**
     * Scope for posts and extensions relevant for newsletter
     */
    public function forNewsletter(DateTimeInterface $since): self
    {
        return $this
            ->published()
            ->where(function (Builder $q) use ($since) {
                // Regular posts published since $since
                $q
                    ->where(function (Builder $q2) use ($since) {
                        $q2
                            ->whereIn('visibility', [Post::VIS_PUBLIC, Post::VIS_UNLISTED])
                            ->where('published_at', '>=', $since);
                    })
                    // OR Extensions attached to public posts since $since
                    ->orWhere(function (Builder $q2) use ($since) {
                        /** @var PostBuilder $q2 */
                        $q2
                            ->extensionType()
                            ->whereHas('parentPosts', function (Builder $q3) use ($since) {
                                $q3
                                    ->whereIn('visibility', [Post::VIS_PUBLIC, Post::VIS_UNLISTED])
                                    ->where('post_extensions.created_at', '>=', $since);
                            });
                    });
            });
    }

    /**
     * Scope for public listing views (includes common select fields)
     */
    public function forPublicListing(): self
    {
        return $this
            ->published()
            ->public()
            ->regularPosts()
            ->orderByPublicationDate()
            ->select(['id', 'blog_id', 'title', 'slug', 'excerpt', 'published_at', 'created_at', 'visibility']);
    }

    /**
     * Scope to find post by slug within published public posts
     */
    public function findBySlugForPublic(string $slug): self
    {
        return $this
            ->forPublicView()
            ->where('slug', $slug);
    }

    /**
     * Scope for posts manageable by a given user
     */
    public function manageableBy(int|User $user): self
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        return $this->where(function (Builder $q) use ($userId) {
            $q
                ->whereHas('blog', fn(Builder $bq) => $bq->where('user_id', $userId))
                ->orWhereHas('group', fn(Builder $gq) => $gq->where('user_id', $userId));
        });
    }

    /**
     * Scope for group posts, visible to logged-in members
     */
    public function forGroupView(): self
    {
        return $this
            ->published()
            ->regularPosts()
            ->orderByPublicationDate();
    }
}
