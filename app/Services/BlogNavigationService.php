<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;

class BlogNavigationService
{
    public function getLandingNavigation(Blog|Group $entity): array
    {
        $query = $entity instanceof Blog
            ? $entity->posts()->forPublicListing()
            : $entity->posts()->forGroupView();

        $latestPost = $query->select(['id', 'title', 'slug'])->first();

        return [
            'prevPost' => null,
            'nextPost' => $latestPost ? $this->formatPostLink($entity, $latestPost) : null,
            'landingUrl' => $this->getLandingUrl($entity),
            'isLandingPage' => true,
            'isGroup' => $entity instanceof Group,
            'breadcrumbs' => $this->buildBreadcrumbs($entity),
        ];
    }

    private function formatPostLink(Blog|Group $entity, Post $post): array
    {
        $routeName = $entity instanceof Blog ? 'blog.public.post' : 'group.post';
        $paramName = $entity instanceof Blog ? 'blog' : 'group';

        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'url' => route($routeName, [$paramName => $entity->slug, 'postSlug' => $post->slug]),
        ];
    }

    private function getLandingUrl(Blog|Group $entity): string
    {
        $routeName = $entity instanceof Blog ? 'blog.public.landing' : 'group.landing';
        $paramName = $entity instanceof Blog ? 'blog' : 'group';

        return route($routeName, [$paramName => $entity->slug]);
    }

    private function buildBreadcrumbs(Blog|Group $entity, ?Post $post = null): array
    {
        $breadcrumbs = [
            [
                'label' => config('app.name'),
                'url' => config('app.url'),
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

    public function getPostNavigation(Blog|Group $entity, Post $post): array
    {
        $prevPost = $this->getAdjacentPost($entity, $post, 'previous');
        $nextPost = $this->getAdjacentPost($entity, $post, 'next');

        return [
            'prevPost' => $prevPost ? $this->formatPostLink($entity, $prevPost) : null,
            'nextPost' => $nextPost ? $this->formatPostLink($entity, $nextPost) : null,
            'landingUrl' => $this->getLandingUrl($entity),
            'isLandingPage' => false,
            'isGroup' => $entity instanceof Group,
            'breadcrumbs' => $this->buildBreadcrumbs($entity, $post),
        ];
    }

    private function getAdjacentPost(Blog|Group $entity, Post $post, string $direction): ?Post
    {
        $compare = $direction === 'next' ? '<' : '>';
        $order = $direction === 'next' ? 'desc' : 'asc';

        $query = $entity->posts()->published();

        if ($entity instanceof Blog) {
            $query->public();
        } else {
            $query->regularPosts();
        }

        return $query
            ->select(['id', 'title', 'slug', 'published_at', 'created_at'])
            ->where(function (Builder $query) use ($post, $compare) {
                $query->where('published_at', $compare, $post->published_at)
                    ->orWhere(function (Builder $subQuery) use ($post, $compare) {
                        $subQuery->where('published_at', '=', $post->published_at)
                            ->where('created_at', $compare, $post->created_at);
                    });
            })
            ->orderBy('published_at', $order)
            ->orderBy('created_at', $order)
            ->first();
    }
}
