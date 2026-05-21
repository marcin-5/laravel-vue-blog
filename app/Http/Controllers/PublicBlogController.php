<?php

namespace App\Http\Controllers;

use App\Builders\PublicBlogSeoBuilder;
use App\Http\Controllers\Concerns\FormatsDatesForLocale;
use App\Http\Controllers\Concerns\FormatsPaginator;
use App\Http\Controllers\Concerns\HandlesViewStats;
use App\Http\Resources\PublicBlogDetailResource;
use App\Http\Resources\PublicBlogResource;
use App\Http\Resources\PublicPostDetailResource;
use App\Http\Resources\PublicPostResource;
use App\Http\Resources\TagResource;
use App\Models\Blog;
use App\Models\Post;
use App\Models\Tag;
use App\Queries\Public\PublicBlogPostsQuery;
use App\Services\BlogNavigationService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use App\Services\StatsService;
use App\Services\TranslationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicBlogController extends BasePublicController
{
    use FormatsDatesForLocale, FormatsPaginator, HandlesViewStats;

    public function __construct(
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
        private readonly PublicBlogSeoBuilder $seoBuilder,
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
        $blog->load(['landingPage', 'user']);

        $paginator = $query->handle($blog);

        $descriptionHtml = str_replace('-!-', '', $this->markdown->convertToHtml($blog->description));
        $metaDescription = $this->seo->generateMetaDescription(
            $descriptionHtml ?: $blog->landingPage?->content_html ?: $blog->name,
        );

        return $this->renderWithTranslations('public/blog/Landing', 'blog', [
            'locale' => app()->getLocale(),
            'blog' => new PublicBlogDetailResource($blog),
            'landingHtml' => $blog->landingPage?->content_html ?? '',
            'footerHtml' => $this->markdown->convertToHtml($blog->footer),
            'posts' => PublicPostResource::collection($paginator->items()),
            'pagination' => $this->formatPagination($paginator),
            'sidebar' => (int) ($blog->sidebar ?? 0),
            'sidebarPosition' => $blog->sidebar_position,
            'navigation' => $this->navigation->getLandingNavigation($blog),
            'seo' => $this->seoBuilder->buildLandingSeo($blog, $paginator, $metaDescription)->toArray(),
            'viewStats' => Inertia::defer(fn() => $this->getViewStats(Blog::class, $blog->id, $blog->user_id)),
            'allTags' => TagResource::collection($blog->tags->sortBy('name')->values()),
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

        $post = $blog
            ->posts()
            ->findBySlugForPublic($postSlug)
            ->with([
                'user',
                'extensions' => fn($q) => $q->where('is_published', true)->oldest(),
                'relatedPosts' => fn($q) => $q->orderBy('display_order'),
                'relatedPosts.relatedPost.blog',
                'externalLinks' => fn($q) => $q->orderBy('display_order'),
            ])
            ->firstOrFail();

        $paginator = $query->handle($blog);
        $paginator->setPath(route('blog.public.landing', ['blog' => $blog->slug]));

        $metaDescription = $post->excerpt ?: $this->seo->generateMetaDescription($post->content_html);

        return $this->renderWithTranslations('public/blog/Post', 'post', [
            'locale' => app()->getLocale(),
            'blog' => new PublicBlogResource($blog),
            'post' => new PublicPostDetailResource($post),
            'posts' => PublicPostResource::collection($paginator->items()),
            'pagination' => $this->formatPagination($paginator),
            'sidebar' => (int) ($blog->sidebar ?? 0),
            'sidebarPosition' => $blog->sidebar_position,
            'navigation' => $this->navigation->getPostNavigation($blog, $post),
            'seo' => $this->seoBuilder->buildPostSeo($blog, $post, $metaDescription)->toArray(),
            'viewStats' => Inertia::defer(fn() => $this->getViewStats(Post::class, $post->id, $blog->user_id)),
            'allTags' => TagResource::collection($blog->tags->sortBy('name')->values()),
        ]);
    }

    /**
     * Show posts filtered by tag within a blog.
     * Route: /{blog:slug}/tags/{tag:slug}
     */
    public function tag(Request $request, Blog $blog, Tag $tag, PublicBlogPostsQuery $query): Response
    {
        $this->ensureBlogIsPublic($blog);

        // Ensure tag belongs to the same blog
        abort_unless($tag->blog_id === $blog->id, 404);

        $blog->load(['landingPage', 'user']);

        $paginator = $query->handle($blog, $tag);

        $descriptionHtml = str_replace('-!-', '', $this->markdown->convertToHtml($blog->description));
        $metaDescription = $this->seo->generateMetaDescription(
            $descriptionHtml ?: $blog->landingPage?->content_html ?: $blog->name,
        );

        return $this->renderWithTranslations('public/blog/Landing', 'blog', [
            'locale' => app()->getLocale(),
            'blog' => new PublicBlogDetailResource($blog),
            'landingHtml' => $blog->landingPage?->content_html ?? '',
            'footerHtml' => $this->markdown->convertToHtml($blog->footer),
            'posts' => PublicPostResource::collection($paginator->items()),
            'pagination' => $this->formatPagination($paginator),
            'sidebar' => (int) ($blog->sidebar ?? 0),
            'sidebarPosition' => $blog->sidebar_position,
            'navigation' => $this->navigation->getLandingNavigation($blog),
            'seo' => $this->seoBuilder->buildLandingSeo($blog, $paginator, $metaDescription, $tag)->toArray(),
            'activeTag' => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
            'viewStats' => Inertia::defer(fn() => $this->getViewStats(Blog::class, $blog->id, $blog->user_id)),
            'allTags' => TagResource::collection($blog->tags->sortBy('name')->values()),
        ]);
    }
}
