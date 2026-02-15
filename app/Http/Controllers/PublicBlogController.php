<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\SeoData;
use App\Http\Controllers\Concerns\FormatsDatesForLocale;
use App\Http\Controllers\Concerns\FormatsPaginator;
use App\Http\Controllers\Concerns\HandlesViewStats;
use App\Http\Resources\PublicBlogResource;
use App\Http\Resources\PublicPostResource;
use App\Models\Blog;
use App\Models\LandingPage;
use App\Models\Post;
use App\Queries\Public\PublicBlogPostsQuery;
use App\Services\BlogNavigationService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use App\Services\StatsService;
use App\Services\TranslationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Response;

class PublicBlogController extends BasePublicController
{
    use FormatsDatesForLocale, FormatsPaginator, HandlesViewStats;

    public function __construct(
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
        private readonly BlogNavigationService $navigation,
        private readonly StatsService $stats,
        protected TranslationService $translations,
    ) {
        parent::__construct($translations);
    }

    /**
     * Show the public landing page for a blog by slug.
     * Route: /{blog:slug}
     */
    public function landing(Request $request, Blog $blog, PublicBlogPostsQuery $query): Response
    {
        $this->ensureBlogIsPublic($blog);

        $landing = $blog->landingPage;
        $paginator = $query->handle($blog);

        // Remove strip markers from the description
        $descriptionHtml = str_replace('-!-', '', $this->markdown->convertToHtml($blog->description));

        $metaDescription = $this->seo->generateMetaDescription(
            $descriptionHtml ?: $landing?->content_html ?: $blog->name,
        );

        $baseUrl = config('app.url');
        $seoData = new SeoData(
            title: $blog->name . ' - ' . config('app.name'),
            description: $metaDescription,
            canonicalUrl: $baseUrl . '/' . $blog->slug,
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
            'viewStats' => $this->getViewStats(Blog::class, $blog->id, $blog->user_id),
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
     * Show a single post by slug for a blog by slug.
     * Route: /{blog:slug}/{post:slug}
     *
     * @throws ModelNotFoundException
     */
    public function post(Request $request, Blog $blog, string $postSlug, PublicBlogPostsQuery $query): Response
    {
        $this->ensureBlogIsPublic($blog);

        $post = $blog->posts()
            ->findBySlugForPublic($postSlug)
            ->firstOrFail();

        $paginator = $query->handle($blog);
        $metaDescription = $post->excerpt ?: $this->seo->generateMetaDescription($post->content_html);

        $baseUrl = config('app.url');
        $seoData = new SeoData(
            title: $post->title . ' - ' . $blog->name,
            description: $metaDescription,
            canonicalUrl: $baseUrl . '/' . $blog->slug . '/' . $post->slug,
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
                'published_at' => $post->published_at?->format('Y-m-d'),
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
            'viewStats' => $this->getViewStats(Post::class, $post->id, $blog->user_id),
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
