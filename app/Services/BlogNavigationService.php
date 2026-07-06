<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

readonly class BlogNavigationService
{
    public function getLandingNavigation(Blog|Group $entity, ?Tag $tag = null): array
    {
        $tagSuffix = $tag ? ":tag:{$tag->id}" : '';
        $locale = app()->getLocale();
        $mainDomain = $locale === 'pl' ? config('app.domain') : config('app.domain_secondary');
        $isExternal = request()->getHost() !== $mainDomain;
        $contextKey = ":{$locale}:" . ($isExternal ? 'external' : 'internal');

        return Cache::tags(["navigation:{$entity->getMorphClass()}:$entity->id"])->remember(
            "landing_navigation:{$entity->getMorphClass()}:$entity->id$tagSuffix$contextKey",
            now()->addMinutes(30),
            fn() => $this->buildLandingNavigation($entity, $tag),
        );
    }

    private function buildLandingNavigation(Blog|Group $entity, ?Tag $tag = null): array
    {
        $query = $entity instanceof Blog
            ? $entity->posts()->forPublicListing()
            : $entity->posts()->forGroupView();

        if ($tag) {
            $query->whereHas('tags', fn(Builder $q) => $q->where('tags.id', $tag->id));
        }

        $latestPost = $query->select(['id', 'title', 'slug'])->first();

        return [
            'prevPost' => null,
            'nextPost' => $latestPost ? $this->formatPostLink($entity, $latestPost, $tag) : null,
            'landingUrl' => $this->getLandingUrl($entity, $tag),
            'isLandingPage' => true,
            'isGroup' => $entity instanceof Group,
            'breadcrumbs' => $this->buildBreadcrumbs($entity),
        ];
    }

    private function formatPostLink(Blog|Group $entity, Post $post, ?Tag $tag = null): array
    {
        $routeName = $entity instanceof Blog ? 'blog.public.post' : 'group.post';
        $paramName = $entity instanceof Blog ? 'blog' : 'group';

        $params = [$paramName => $entity->slug, 'postSlug' => $post->slug];

        if ($entity instanceof Blog) {
            $params['mainDomain'] = $entity->locale === 'pl' ? config('app.domain') : config('app.domain_secondary');
        }

        if ($tag) {
            $params['tag'] = $tag->slug;
        }

        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'url' => route($routeName, $params),
        ];
    }

    private function getLandingUrl(Blog|Group $entity, ?Tag $tag = null): string
    {
        if ($tag && $entity instanceof Blog) {
            $mainDomain = $entity->locale === 'pl' ? config('app.domain') : config('app.domain_secondary');

            return route('blog.public.tag', ['blog' => $entity->slug, 'tag' => $tag->slug, 'mainDomain' => $mainDomain]);
        }

        $routeName = $entity instanceof Blog ? 'blog.public.landing' : 'group.landing';
        $paramName = $entity instanceof Blog ? 'blog' : 'group';

        $params = [$paramName => $entity->slug];
        if ($entity instanceof Blog) {
            $params['mainDomain'] = $entity->locale === 'pl' ? config('app.domain') : config('app.domain_secondary');
        }

        return route($routeName, $params);
    }

    private function buildBreadcrumbs(Blog|Group $entity, ?Post $post = null): array
    {
        $mainDomain = app()->getLocale() === 'pl' ? config('app.domain') : config('app.domain_secondary');
        $homeUrl = (request()->isSecure() ? 'https://' : 'http://') . $mainDomain;

        $breadcrumbs = [
            [
                'label' => config('app.name'),
                'url' => $homeUrl,
                'is_external' => request()->getHost() !== $mainDomain,
            ],
            [
                'label' => $entity->name,
                'url' => $this->getLandingUrl($entity),
            ],
        ];

        if ($post) {
            $breadcrumbs[] = [
                'label' => $post->title,
                'url' => null,
            ];
        }

        return $breadcrumbs;
    }

    public function getPostNavigation(Blog|Group $entity, Post $post, ?Tag $tag = null): array
    {
        $tagSuffix = $tag ? ":tag:{$tag->id}" : '';
        $locale = app()->getLocale();
        $mainDomain = $locale === 'pl' ? config('app.domain') : config('app.domain_secondary');
        $isExternal = request()->getHost() !== $mainDomain;
        $contextKey = ":{$locale}:" . ($isExternal ? 'external' : 'internal');

        return Cache::tags(["navigation:{$entity->getMorphClass()}:$entity->id"])->remember(
            "post_navigation:{$entity->getMorphClass()}:$entity->id:$post->id$tagSuffix$contextKey",
            now()->addMinutes(30),
            fn() => $this->buildPostNavigation($entity, $post, $tag),
        );
    }

    private function buildPostNavigation(Blog|Group $entity, Post $post, ?Tag $tag = null): array
    {
        $prevPost = $this->getAdjacentPost($entity, $post, 'previous', $tag);
        $nextPost = $this->getAdjacentPost($entity, $post, 'next', $tag);

        return [
            'prevPost' => $prevPost ? $this->formatPostLink($entity, $prevPost, $tag) : null,
            'nextPost' => $nextPost ? $this->formatPostLink($entity, $nextPost, $tag) : null,
            'landingUrl' => $this->getLandingUrl($entity, $tag),
            'isLandingPage' => false,
            'isGroup' => $entity instanceof Group,
            'breadcrumbs' => $this->buildBreadcrumbs($entity, $post),
        ];
    }

    private function getAdjacentPost(Blog|Group $entity, Post $post, string $direction, ?Tag $tag = null): ?Post
    {
        $compare = $direction === 'next' ? '<' : '>';
        $order = $direction === 'next' ? 'desc' : 'asc';

        $query = $entity->posts()->published();

        if ($entity instanceof Blog) {
            $query->public();
        } else {
            $query->regularPosts();
        }

        if ($tag) {
            $query->whereHas('tags', fn(Builder $q) => $q->where('tags.id', $tag->id));
        }

        return $query
            ->select(['id', 'title', 'slug', 'published_at', 'created_at'])
            ->where(function (Builder $query) use ($post, $compare) {
                $query
                    ->where('published_at', $compare, $post->published_at)
                    ->orWhere(function (Builder $subQuery) use ($post, $compare) {
                        $subQuery
                            ->where('published_at', '=', $post->published_at)
                            ->where('created_at', $compare, $post->created_at);
                    });
            })
            ->orderBy('published_at', $order)
            ->orderBy('created_at', $order)
            ->first();
    }
}
