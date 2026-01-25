<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\PageView;
use App\Models\Post;
use App\Services\BlogNavigationService;
use App\Services\TranslationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly BlogNavigationService $navigation,
        private readonly TranslationService $translations,
    ) {
    }

    public function landing(Request $request, Group $group): Response
    {
        $this->authorize('view', $group);

        $posts = $group->posts()
            ->forGroupView()
            ->paginate($group->page_size ?? 15);

        return Inertia::render('app/group/Landing', [
            'group' => $this->formatGroup($group),
            'authorName' => $group->user?->name,
            'authorEmail' => $group->user?->email,
            'posts' => $posts->items(),
            'pagination' => $this->formatPagination($posts),
            'theme' => $group->theme,
            'sidebar' => $group->sidebar,
            'navigation' => $this->navigation->getGroupLandingNavigation($group),
            'viewStats' => [
                'total' => $group->view_count,
                'unique' => $this->countUniqueViews((new Group)->getMorphClass(), $group->id),
            ],
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

    private function formatPagination($paginator): array
    {
        $links = $paginator->linkCollection()->toArray();

        return [
            'links' => array_map(function ($lnk) {
                return [
                    'url' => $lnk['url'] ?? null,
                    'label' => $lnk['label'] ?? '',
                    'active' => (bool)($lnk['active'] ?? false),
                ];
            }, $links),
            'prevUrl' => $paginator->previousPageUrl(),
            'nextUrl' => $paginator->nextPageUrl(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }

    private function countUniqueViews(string $morphClass, int $id): int
    {
        $table = 'page_views';
        $sql = "(
            CASE
              WHEN {$table}.user_id IS NOT NULL THEN CONCAT('U:', {$table}.user_id)
              WHEN {$table}.visitor_id IS NOT NULL AND {$table}.visitor_id <> '' THEN CONCAT('V:', {$table}.visitor_id)
              WHEN {$table}.fingerprint IS NOT NULL AND {$table}.fingerprint <> '' THEN CONCAT('F:', {$table}.fingerprint)
              WHEN {$table}.session_id IS NOT NULL AND {$table}.session_id <> '' THEN CONCAT('S:', {$table}.session_id)
              ELSE CONCAT('I:', COALESCE({$table}.ip_address, ''))
            END
        )";

        /** @var int $count */
        $count = PageView::query()
            ->where('viewable_type', $morphClass)
            ->where('viewable_id', $id)
            ->selectRaw("COUNT(DISTINCT ($sql)) as cnt")
            ->value('cnt');

        return $count;
    }

    public function post(Request $request, Group $group, string $postSlug): Response
    {
        $this->authorize('view', $group);

        $post = $group->posts()
            ->where('slug', $postSlug)
            ->forGroupView()
            ->firstOrFail();

        $paginatedPosts = $group->posts()
            ->forGroupView()
            ->paginate($group->page_size ?? 15);

        return Inertia::render('app/group/Post', [
            'group' => $group,
            'post' => $this->formatPost($post, $group),
            'posts' => $paginatedPosts->items(),
            'pagination' => $this->formatPagination($paginatedPosts),
            'theme' => $group->theme,
            'sidebar' => $group->sidebar,
            'navigation' => $this->navigation->getGroupPostNavigation($group, $post),
            'viewStats' => [
                'total' => $post->view_count,
                'unique' => $this->countUniqueViews((new Post)->getMorphClass(), $post->id),
            ],
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
