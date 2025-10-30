<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\SeoData;
use App\Http\Controllers\Concerns\FormatsDatesForLocale;
use App\Http\Resources\PublicBlogResource;
use App\Http\Resources\PublicPostResource;
use App\Models\Blog;
use App\Models\LandingPage;
use App\Services\BlogContentService;
use App\Services\BlogNavigationService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use App\Services\TranslationService;
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
        private readonly BlogContentService $content,
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

        $descriptionHtml = $this->markdown->convertToHtml($blog->description);
        $descriptionHtml = $this->content->enrichDescriptionWithAuthor($descriptionHtml, $blog);

        $metaDescription = $this->seo->generateMetaDescription(
            $descriptionHtml ?: $landing?->content_html ?: $blog->name,
        );

        $baseUrl = config('app.url');
        $seoData = new SeoData(
            title: $blog->name,
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

        return $this->renderWithTranslations('Blog/Landing', 'blog', [
            'locale' => app()->getLocale(),
            'blog' => (new PublicBlogResource($blog))->toArray($request) + [
                    'descriptionHtml' => $descriptionHtml,
                ],
            'landingHtml' => $landing?->content_html ?? '',
            'footerHtml' => $this->markdown->convertToHtml($blog->footer),
            'metaDescription' => $metaDescription,
            'posts' => PublicPostResource::collection($paginator->items())->toArray($request),
            'pagination' => $this->formatPagination($paginator),
            'sidebar' => (int)($blog->sidebar ?? 0),
            'navigation' => $this->navigation->getLandingNavigation($blog),
            'seo' => $seoData->toArray(),
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

    /**
     * Show a single post by slug for a blog by slug.
     * Route: /{blog:slug}/{post:slug}
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

        return $this->renderWithTranslations('Blog/Post', 'post', [
            'locale' => app()->getLocale(),
            'blog' => new PublicBlogResource($blog)->toArray($request),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'author' => $blog->user->name,
                'author_email' => $blog->user->email,
                'contentHtml' => $post->content_html,
                'published_at' => $this->formatDateForLocale($post->published_at),
                'excerpt' => $post->excerpt,
            ],
            'posts' => PublicPostResource::collection($paginator->items())->toArray($request),
            'pagination' => $this->formatPagination($paginator),
            'sidebarPosition' => $this->getSidebarPosition($blog),
            'sidebar' => (int)($blog->sidebar ?? 0),
            'navigation' => $this->navigation->getPostNavigation($blog, $post),
            'seo' => $seoData->toArray(),
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
