<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsPaginator;
use App\Http\Controllers\Concerns\HandlesViewStats;
use App\Models\Group;
use App\Models\Post;
use App\Queries\App\GroupPostsQuery;
use App\Services\BlogNavigationService;
use App\Services\StatsService;
use App\Services\TranslationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    use AuthorizesRequests, FormatsPaginator, HandlesViewStats;

    public function __construct(
        private readonly BlogNavigationService $navigation,
        private readonly TranslationService $translations,
        private readonly StatsService $stats,
    ) {
    }

    public function landing(Request $request, Group $group, GroupPostsQuery $query): Response
    {
        $this->authorize('view', $group);

        $posts = $query->handle($group);

        return Inertia::render('app/group/Landing', [
            'group' => $this->formatGroup($group),
            'authorName' => $group->user?->name,
            'authorEmail' => $group->user?->email,
            'posts' => $posts->items(),
            'pagination' => $this->formatPagination($posts),
            'theme' => $group->theme,
            'sidebar' => $group->sidebar,
            'navigation' => $this->navigation->getGroupLandingNavigation($group),
            'viewStats' => $this->getViewStats(Group::class, $group->id, $group->user_id, true),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('blog'),
            ],
        ]);
    }

    private function formatGroup(Group $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'slug' => $group->slug,
            'content' => $group->content_html,
            'footer' => $group->footer_html,
            'created_at' => $group->created_at?->format('Y-m-d H:i'),
            'updated_at' => $group->updated_at?->format('Y-m-d H:i'),
        ];
    }

    public function post(Request $request, Group $group, string $postSlug, GroupPostsQuery $query): Response
    {
        $this->authorize('view', $group);

        $post = $group->posts()
            ->where('slug', $postSlug)
            ->forGroupView()
            ->firstOrFail();

        $paginatedPosts = $query->handle($group);

        return Inertia::render('app/group/Post', [
            'group' => $group,
            'post' => $this->formatPost($post, $group),
            'posts' => $paginatedPosts->items(),
            'pagination' => $this->formatPagination($paginatedPosts),
            'theme' => $group->theme,
            'sidebar' => $group->sidebar,
            'navigation' => $this->navigation->getGroupPostNavigation($group, $post),
            'viewStats' => $this->getViewStats(Post::class, $post->id, $group->user_id, true),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('post'),
            ],
        ]);
    }

    private function formatPost(Post $post, Group $group): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'author' => $post->user?->name ?? $group->user?->name,
            'author_email' => $post->user?->email ?? $group->user?->email,
            'contentHtml' => $post->content_html,
            'published_at' => $post->published_at?->format('Y-m-d H:i'),
            'excerpt' => $post->excerpt,
            'extensions' => $post->extensions()
                ->where('is_published', true)
                ->oldest()
                ->get()
                ->map(fn($ext) => [
                    'id' => $ext->id,
                    'title' => $ext->title,
                    'contentHtml' => $ext->content_html,
                ]),
        ];
    }
}
