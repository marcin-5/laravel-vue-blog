<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\SeoData;
use App\Http\Controllers\Concerns\FormatsDatesForLocale;
use App\Http\Resources\PublicBlogResource;
use App\Http\Resources\PublicPostResource;
use App\Models\Blog;
use App\Models\LandingPage;
use App\Models\PageView;
use App\Models\Post;
use App\Services\BlogNavigationService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use App\Services\TranslationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Response;
use Number;

class PublicBlogController extends BasePublicController
{
    use FormatsDatesForLocale;

    public function __construct(
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
        private readonly BlogNavigationService $navigation,
        protected TranslationService $translations,
    ) {
        parent::__construct($translations);
    }

    /**
     * Show the public landing page for a blog by slug.
     * Route: /{blog:slug}
     */
    public function landing(Request $request, Blog $blog): Response
    {
        $this->ensureBlogIsPublic($blog);

        $landing = $blog->landingPage;
        $paginator = $this->getPaginatedPosts($blog);

        // Remove strip markers from the description
        $descriptionHtml = str_replace('-!-', '', $this->markdown->convertToHtml($blog->description));

        $metaDescription = $this->seo->generateMetaDescription(
            $descriptionHtml ?: $landing?->content_html ?: $blog->name,
        );

        $baseUrl = config('app.url');
        $seoData = new SeoData(
            title: $blog->name . ' - ' . config('app.name'),
            description: $metaDescription,
            canonicalUrl: $baseUrl . '/blogs/' . $blog->slug,
            ogImage: $baseUrl . '/og-image.png',
            ogType: 'blog',
            locale: app()->getLocale(),
            structuredData: $this->seo->generateBlogStructuredData(
                $blog,
                $paginator->items(),
                $baseUrl,
                $metaDescription,
            ),
        );

        return $this->renderWithTranslations('public/blog/Landing', 'blog', [
            'locale' => app()->getLocale(),
            'blog' => new PublicBlogResource($blog)->toArray($request) + [
                    'descriptionHtml' => $descriptionHtml,
                    'authorName' => $blog->user?->name,
                    'authorEmail' => $blog->user?->email,
                ],
            'landingHtml' => $landing?->content_html ?? '',
            'footerHtml' => $this->markdown->convertToHtml($blog->footer),
            'metaDescription' => $metaDescription,
            'posts' => PublicPostResource::collection($paginator->items())->toArray($request),
            'pagination' => $this->formatPagination($paginator),
            'sidebar' => (int)($blog->sidebar ?? 0),
            'navigation' => $this->navigation->getLandingNavigation($blog),
            'seo' => $seoData->toArray(),
            'viewStats' => [
                'total' => $blog->view_count,
                'unique' => auth()->check() && auth()->user()->isAdmin()
                    ? $this->countUniqueViews((new Blog)->getMorphClass(), $blog->id)
                    : null,
            ],
        ]);
    }

    /**
     * Ensure blog is published and set locale.
     */
    private function ensureBlogIsPublic(Blog $blog): void
    {
        abort_unless($blog->is_published, 404);
        app()->setLocale($blog->locale ?? config('app.locale'));
    }

    /**
     * Get published, public posts for a blog.
     */
    private function getPaginatedPosts(Blog $blog)
    {
        $size = Number::clamp(
            (int)($blog->page_size ?? config('blog.default_page_size')),
            1,
            config('blog.max_page_size'),
        );

        return $blog->posts()
            ->forPublicListing()
            ->paginate($size)
            ->withQueryString();
    }

    /**
     * Format Laravel LengthAwarePaginator into a simple structure for the front-end.
     */
    private function formatPagination($paginator): array
    {
        if (!$paginator) {
            return [];
        }

        // Use the built-in pagination links array, but also expose prev/next urls
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
        ];
    }

    private function countUniqueViews(string $morphClass, int $id): int
    {
        // Build CASE expression identical to StatsService::uniqueViewerKeySql()
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

        return (int)$count;
    }

    /**
     * Show a single post by slug for a blog by slug.
     * Route: /{blog:slug}/{post:slug}
     *
     * @throws ModelNotFoundException
     */
    public function post(Request $request, Blog $blog, string $postSlug): Response
    {
        $this->ensureBlogIsPublic($blog);

        $post = $blog->posts()
            ->findBySlugForPublic($postSlug)
            ->firstOrFail();

        $paginator = $this->getPaginatedPosts($blog);
        $metaDescription = $post->excerpt ?: $this->seo->generateMetaDescription($post->content_html);

        $baseUrl = config('app.url');
        $seoData = new SeoData(
            title: $post->title . ' - ' . $blog->name,
            description: $metaDescription,
            canonicalUrl: $baseUrl . '/blogs/' . $blog->slug . '/' . $post->slug,
            ogImage: $baseUrl . '/og-image.png',
            ogType: 'article',
            locale: app()->getLocale(),
            structuredData: $this->seo->generatePostStructuredData($blog, $post, $baseUrl, $metaDescription),
            publishedTime: $post->published_at?->toIso8601String(),
            modifiedTime: $post->updated_at?->toIso8601String(),
        );

        return $this->renderWithTranslations('public/blog/Post', 'post', [
            'locale' => app()->getLocale(),
            'blog' => new PublicBlogResource($blog)->toArray($request),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'author' => $post->user?->name ?? $blog->user->name,
                'author_email' => $post->user?->email ?? $blog->user->email,
                'contentHtml' => $post->content_html,
                'published_at' => $this->formatDateForLocale($post->published_at),
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
            ],
            'posts' => PublicPostResource::collection($paginator->items())->toArray($request),
            'pagination' => $this->formatPagination($paginator),
            'sidebarPosition' => $this->getSidebarPosition($blog),
            'sidebar' => (int)($blog->sidebar ?? 0),
            'navigation' => $this->navigation->getPostNavigation($blog, $post),
            'seo' => $seoData->toArray(),
            'viewStats' => [
                'total' => $post->view_count,
                'unique' => $this->countUniqueViews((new Post)->getMorphClass(), $post->id),
            ],
        ]);
    }

    /**
     * Determine sidebar placement from landing page settings.
     */
    private function getSidebarPosition(Blog $blog): string
    {
        // kept for backward compatibility (used on Post page props)
        if (($blog->sidebar ?? 0) === 0) {
            return LandingPage::SIDEBAR_NONE;
        }

        return ($blog->sidebar ?? 0) < 0 ? LandingPage::SIDEBAR_LEFT : LandingPage::SIDEBAR_RIGHT;
    }
}
