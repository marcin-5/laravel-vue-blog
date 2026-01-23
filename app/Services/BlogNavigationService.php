<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;

class BlogNavigationService
{
    public function getLandingNavigation(Blog $blog): array
    {
        $latestPost = $blog->posts()
            ->forPublicListing()
            ->select(['id', 'title', 'slug'])
            ->first();

        return [
            'prevPost' => null,
            'nextPost' => $latestPost ? $this->formatPostLink($blog, $latestPost) : null,
            'landingUrl' => route('blog.public.landing', ['blog' => $blog->slug]),
            'isLandingPage' => true,
            'breadcrumbs' => $this->buildBreadcrumbs($blog),
        ];
    }

    private function formatPostLink(Blog $blog, Post $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'url' => route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $post->slug]),
        ];
    }

    private function buildBreadcrumbs(Blog $blog, ?Post $post = null): array
    {
        $breadcrumbs = [
            [
                'label' => config('app.name'),
                'url' => config('app.url'),
            ],
            [
                'label' => $blog->name,
                'url' => route('blog.public.landing', ['blog' => $blog->slug]),
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

    public function getPostNavigation(Blog $blog, Post $post): array
    {
        $prevPost = $this->getAdjacentPost($blog, $post, 'previous');
        $nextPost = $this->getAdjacentPost($blog, $post, 'next');

        return [
            'prevPost' => $prevPost ? $this->formatPostLink($blog, $prevPost) : null,
            'nextPost' => $nextPost ? $this->formatPostLink($blog, $nextPost) : null,
            'landingUrl' => route('blog.public.landing', ['blog' => $blog->slug]),
            'isLandingPage' => false,
            'breadcrumbs' => $this->buildBreadcrumbs($blog, $post),
        ];
    }

    private function getAdjacentPost(Blog $blog, Post $post, string $direction): ?Post
    {
        $compare = $direction === 'next' ? '<' : '>';
        $order = $direction === 'next' ? 'desc' : 'asc';

        return $blog->posts()
            ->published()
            ->public()
            ->select(['id', 'title', 'slug', 'published_at', 'created_at'])
            ->where(function ($query) use ($post, $compare) {
                $query->where('published_at', $compare, $post->published_at)
                    ->orWhere(function ($subQuery) use ($post, $compare) {
                        $subQuery->where('published_at', '=', $post->published_at)
                            ->where('created_at', $compare, $post->created_at);
                    });
            })
            ->orderBy('published_at', $order)
            ->orderBy('created_at', $order)
            ->first();
    }

    public function getGroupLandingNavigation(Group $group): array
    {
        $latestPost = $group->posts()
            ->forGroupView()
            ->select(['id', 'title', 'slug'])
            ->first();

        return [
            'prevPost' => null,
            'nextPost' => $latestPost ? $this->formatGroupPostLink($group, $latestPost) : null,
            'landingUrl' => route('group.landing', ['group' => $group->slug]),
            'isLandingPage' => true,
            'isGroup' => true,
            'breadcrumbs' => $this->buildGroupBreadcrumbs($group),
        ];
    }

    private function formatGroupPostLink(Group $group, Post $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'url' => route('group.post', ['group' => $group->slug, 'postSlug' => $post->slug]),
        ];
    }

    private function buildGroupBreadcrumbs(Group $group, ?Post $post = null): array
    {
        $breadcrumbs = [
            [
                'label' => config('app.name'),
                'url' => config('app.url'),
            ],
            [
                'label' => $group->name,
                'url' => route('group.landing', ['group' => $group->slug]),
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

    public function getGroupPostNavigation(Group $group, Post $post): array
    {
        $prevPost = $this->getAdjacentGroupPost($group, $post, 'previous');
        $nextPost = $this->getAdjacentGroupPost($group, $post, 'next');

        return [
            'prevPost' => $prevPost ? $this->formatGroupPostLink($group, $prevPost) : null,
            'nextPost' => $nextPost ? $this->formatGroupPostLink($group, $nextPost) : null,
            'landingUrl' => route('group.landing', ['group' => $group->slug]),
            'isLandingPage' => false,
            'isGroup' => true,
            'breadcrumbs' => $this->buildGroupBreadcrumbs($group, $post),
        ];
    }

    private function getAdjacentGroupPost(Group $group, Post $post, string $direction): ?Post
    {
        $compare = $direction === 'next' ? '<' : '>';
        $order = $direction === 'next' ? 'desc' : 'asc';

        return $group->posts()
            ->published()
            ->regularPosts()
            ->select(['id', 'title', 'slug', 'published_at', 'created_at'])
            ->where(function ($query) use ($post, $compare) {
                $query->where('published_at', $compare, $post->published_at)
                    ->orWhere(function ($subQuery) use ($post, $compare) {
                        $subQuery->where('published_at', '=', $post->published_at)
                            ->where('created_at', $compare, $post->created_at);
                    });
            })
            ->orderBy('published_at', $order)
            ->orderBy('created_at', $order)
            ->first();
    }
}
