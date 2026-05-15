<?php

namespace App\Http\Controllers;

use App\Builders\GroupSeoBuilder;
use App\Http\Controllers\Concerns\FormatsPaginator;
use App\Http\Resources\GroupPostResource;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Queries\App\GroupPostsQuery;
use App\Services\BlogNavigationService;
use App\Services\TranslationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    use AuthorizesRequests, FormatsPaginator;

    public function __construct(
        private readonly BlogNavigationService $navigation,
        private readonly TranslationService $translations,
        private readonly GroupSeoBuilder $seoBuilder,
    ) {}

    public function landing(Request $request, Group $group, GroupPostsQuery $query): Response
    {
        $this->authorize('view', $group);

        $group->load('user');
        $posts = $query->handle($group);

        return Inertia::render('app/group/Landing', [
            'group' => new GroupResource($group),
            'authorName' => $group->user?->name,
            'authorEmail' => $group->user?->email,
            'posts' => GroupPostResource::collection($posts->items()),
            'pagination' => $this->formatPagination($posts),
            'theme' => $group->theme,
            'sidebar' => $group->sidebar,
            'navigation' => $this->navigation->getLandingNavigation($group),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('blog'),
            ],
            'seo' => $this->seoBuilder->buildLandingSeo($group)->toArray(),
        ]);
    }

    public function post(Request $request, Group $group, string $postSlug, GroupPostsQuery $query): Response
    {
        $this->authorize('view', $group);

        $post = $group
            ->posts()
            ->where('slug', $postSlug)
            ->forGroupView()
            ->with(['user', 'extensions', 'group.user'])
            ->firstOrFail();

        $paginatedPosts = $query->handle($group);

        return Inertia::render('app/group/Post', [
            'group' => new GroupResource($group),
            'post' => new GroupPostResource($post),
            'posts' => GroupPostResource::collection($paginatedPosts->items()),
            'pagination' => $this->formatPagination($paginatedPosts),
            'theme' => $group->theme,
            'sidebar' => $group->sidebar,
            'navigation' => $this->navigation->getPostNavigation($group, $post),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('post'),
            ],
            'seo' => $this->seoBuilder->buildPostSeo($group, $post)->toArray(),
        ]);
    }
}
