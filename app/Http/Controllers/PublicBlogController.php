<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsDatesForLocale;
use App\Models\Blog;
use App\Models\LandingPage;
use App\Models\Post;
use App\Services\MarkdownService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Response;
use Number;

class PublicBlogController extends BasePublicController
{
    use FormatsDatesForLocale;

    /**
     * Show the public landing page for a blog by slug.
     * Route: /{blog:slug}
     */
    public function landing(Request $request, Blog $blog): Response
    {
        $this->preparePublicBlog($blog);

        return $this->renderWithTranslations('Blog/Landing', 'blog', $this->getLandingViewProps($blog));
    }

    /**
     * Ensure blog is published and set locale.
     */
    private function preparePublicBlog(Blog $blog): void
    {
        abort_unless($blog->is_published, 404);
        app()->setLocale($blog->locale ?? config('app.locale'));
    }

    /**
     * Prepare properties for the landing page view.
     */
    private function getLandingViewProps(Blog $blog): array
    {
        $landing = $blog->landingPage;
        $paginator = $this->getPublicPostsPaginated($blog);
        $descriptionHtml = $this->parseMarkdownToHtml($blog->description);
        $footerHtml = $this->parseMarkdownToHtml($blog->footer ?? null);
        $metaDescription = $this->generateMetaDescription($blog, $descriptionHtml, $landing);

        // Insert the owner's name preceding footnotes, or at the end elsewhere.
        if ($descriptionHtml !== '') {
            // Prepare the owner line (escape content to be safe)
            $ownerName = e(optional($blog->user)->name ?? '');
            $ownerEmail = e(optional($blog->user)->email ?? '');

            if ($ownerName !== '' && $ownerEmail !== '') {
                $ownerLine = '<p class="author text-md text-slate-700 dark:text-slate-300 mr-12 text-end" style="font-family: \'Noto Serif\', serif">'
                    . '<a href="mailto:' . $ownerEmail . '">' . $ownerName . '</a>'
                    . '</p>';

                $footnotesMarker = '<div class="footnotes">';
                if (str_contains($descriptionHtml, $footnotesMarker)) {
                    // Insert right before the footnotes div
                    $descriptionHtml = str_replace($footnotesMarker, $ownerLine . $footnotesMarker, $descriptionHtml);
                } else {
                    // Append after landing content
                    $descriptionHtml .= $ownerLine;
                }
            }
        }

        // Get navigation for landing page with correct disabled states
        $navigation = $this->getLandingNavigation($blog);

        $baseUrl = config('app.url');
        $canonicalUrl = $baseUrl . '/blogs/' . $blog->slug;
        $ogImage = $baseUrl . '/og-image.png';

        return [
            'locale' => app()->getLocale(),
            'blog' => [
                'id' => $blog->id,
                'name' => $blog->name,
                'slug' => $blog->slug,
                'descriptionHtml' => $descriptionHtml,
                'motto' => $blog->motto,
            ],
            'landingHtml' => $landing?->content_html ?? '',
            'footerHtml' => $footerHtml,
            'metaDescription' => $metaDescription,
            'posts' => $this->formatPostsForView(collect($paginator->items())),
            'pagination' => $this->formatPagination($paginator),
            'sidebar' => (int)($blog->sidebar ?? 0),
            'navigation' => $navigation,
            // SEO meta data for SSR
            'seo' => [
                'title' => $blog->name,
                'description' => $metaDescription,
                'canonicalUrl' => $canonicalUrl,
                'ogImage' => $ogImage,
                'ogType' => 'blog',
                'locale' => app()->getLocale(),
                'structuredData' => $this->generateBlogStructuredData(
                    $blog,
                    $paginator->items(),
                    $baseUrl,
                    $metaDescription,
                ),
            ],
        ];
    }

    /**
     * Get published, public posts for a blog.
     */
    private function getPublicPostsPaginated(Blog $blog)
    {
        $size = Number::clamp((int)($blog->page_size ?? 10), 1, 100);
        $query = $blog->posts()
            ->published()
            ->public()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->select(['id', 'blog_id', 'title', 'slug', 'excerpt', 'published_at', 'created_at']);

        return $query->paginate($size)->withQueryString();
    }

    /**
     * Parse Markdown to safe HTML.
     */
    private function parseMarkdownToHtml(?string $markdown): string
    {
        if (empty($markdown)) {
            return '';
        }

        /** @var MarkdownService $md */
        $md = app(MarkdownService::class);
        return $md->convertToHtml($markdown);
    }

    /**
     * Build plain-text meta description for SEO.
     */
    private function generateMetaDescription(Blog $blog, string $descriptionHtml, ?LandingPage $landing): string
    {
        $source = $descriptionHtml ?: $landing?->content_html ?: $blog->name ?? '';

        // Strip HTML tags and clean up the text
        $text = strip_tags($source);

        // Convert Markdown leftovers and remove formatting
        $patterns = [
            '/!\[([^]]*)]\([^)]+\)/' => '$1',    // images -> alt
            '/\[([^]]+)]\([^)]+\)/' => '$1',     // links -> text
            '/(\*\*|__|\*|_|`)/' => '',          // emphasis markers
            '/^\s{0,3}[#>\-]+\s*/m' => '',       // heading/blockquote/list markers
        ];
        $text = preg_replace(array_keys($patterns), array_values($patterns), $text);

        // Collapse whitespace and decode HTML entities
        $text = Str::squish(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));

        // Truncate to ~160 chars without cutting words
        $limit = 160;
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $text = mb_substr($text, 0, $limit);
        $cutPosition = mb_strrpos($text, ' ');

        // Cut at last space, but only if it's reasonably far from the start
        if ($cutPosition !== false && $cutPosition > 80) {
            $text = mb_substr($text, 0, $cutPosition);
        }

        return rtrim($text) . '…';
    }

    /**
     * Get navigation data for landing page.
     */
    private function getLandingNavigation(Blog $blog): array
    {
        // Get the latest post for the next button
        $latestPost = $blog->posts()
            ->published()
            ->public()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->select(['id', 'title', 'slug'])
            ->first();

        $appName = config('app.name');
        $baseUrl = config('app.url');

        return [
            'prevPost' => null, // Always disabled on landing page
            'nextPost' => $latestPost ? [
                'title' => $latestPost->title,
                'slug' => $latestPost->slug,
                'url' => route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $latestPost->slug])
            ] : null,
            'landingUrl' => route('blog.public.landing', ['blog' => $blog->slug]),
            'isLandingPage' => true, // Flag to indicate this is landing page navigation
            'breadcrumbs' => [
                [
                    'label' => $appName,
                    'url' => $baseUrl,
                ],
                [
                    'label' => $blog->name,
                    'url' => route('blog.public.landing', ['blog' => $blog->slug]),
                ],
            ],
        ];
    }

    /**
     * Format posts for the Inertia view.
     */
    private function formatPostsForView($posts): Collection
    {
        // $posts can be a collection or array of Post
        return collect($posts)->map(fn(Post $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            // Convert excerpt markdown to sanitized HTML for display
            'excerpt' => $this->parseMarkdownToHtml($p->excerpt),
            'published_at' => $this->formatDateForLocale($p->published_at),
        ])->values();
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
     * Generate structured data for blog landing page.
     */
    private function generateBlogStructuredData(Blog $blog, $posts, string $baseUrl, string $plainDescription): array
    {
        $blogUrl = $baseUrl . '/blogs/' . $blog->slug;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => $blog->name,
            'url' => $blogUrl,
            'description' => $plainDescription,
            'author' => [
                '@type' => 'Organization',
                'name' => $blog->name,
            ],
            'blogPost' => collect($posts)->map(function ($post) use ($blog, $baseUrl) {
                return [
                    '@type' => 'BlogPosting',
                    'headline' => $post->title,
                    'url' => $baseUrl . '/blogs/' . $blog->slug . '/' . $post->slug,
                    'datePublished' => $post->published_at?->toIso8601String(),
                    'description' => $post->excerpt ? strip_tags($post->excerpt) : null,
                ];
            })->all(),
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'item' => [
                            '@id' => $baseUrl,
                            'name' => config('app.name'),
                        ],
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'item' => [
                            '@id' => $blogUrl,
                            'name' => $blog->name,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Show a single post by slug for a blog by slug.
     * Route: /{blog:slug}/{post:slug}
     */
    public function post(Request $request, Blog $blog, string $postSlug): Response
    {
        $this->preparePublicBlog($blog);
        $post = $this->getPublicPost($blog, $postSlug);

        return $this->renderWithTranslations('Blog/Post', 'post', $this->getPostViewProps($blog, $post));
    }

    /**
     * Get a single published, public post for a blog by slug.
     */
    private function getPublicPost(Blog $blog, string $postSlug): Post
    {
        return $blog->posts()
            ->where('slug', $postSlug)
            ->published()
            ->public()
            ->firstOrFail();
    }

    /**
     * Prepare properties for the single post page view.
     */
    private function getPostViewProps(Blog $blog, Post $post): array
    {
        $paginator = $this->getPublicPostsPaginated($blog);
        $navigation = $this->getPostNavigation($blog, $post);

        $baseUrl = config('app.url');
        $canonicalUrl = $baseUrl . '/blogs/' . $blog->slug . '/' . $post->slug;
        $ogImage = $baseUrl . '/og-image.png';

        // Generate meta description from post content
        $metaDescription = $post->excerpt ?: $this->generatePostMetaDescription($post->content_html);

        return [
            'locale' => app()->getLocale(),
            'blog' => [
                'id' => $blog->id,
                'name' => $blog->name,
                'slug' => $blog->slug,
                'motto' => $blog->motto,
            ],
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
            'posts' => $this->formatPostsForView(collect($paginator->items())),
            'pagination' => $this->formatPagination($paginator),
            'sidebarPosition' => $this->getSidebarPosition($blog),
            'sidebar' => (int)($blog->sidebar ?? 0),
            'navigation' => $navigation,
            // SEO meta data for SSR
            'seo' => [
                'title' => $post->title . ' - ' . $blog->name,
                'description' => $metaDescription,
                'canonicalUrl' => $canonicalUrl,
                'ogImage' => $ogImage,
                'ogType' => 'article',
                'locale' => app()->getLocale(),
                'publishedTime' => optional($post->published_at)?->toIso8601String(),
                'modifiedTime' => optional($post->updated_at)?->toIso8601String(),
                'structuredData' => $this->generatePostStructuredData($blog, $post, $baseUrl, $metaDescription),
            ],
        ];
    }

    /**
     * Get navigation data for previous and next posts.
     * Previous = newer post, Next = older post (chronological navigation)
     */
    private function getPostNavigation(Blog $blog, Post $post): array
    {
        $prevPost = $this->getAdjacentPost($blog, $post, 'previous');
        $nextPost = $this->getAdjacentPost($blog, $post, 'next');

        $formatUrl = fn(Post $p) => [
            'title' => $p->title,
            'slug' => $p->slug,
            'url' => route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $p->slug]),
        ];

        $appName = config('app.name');
        $baseUrl = config('app.url');

        return [
            'prevPost' => $prevPost ? $formatUrl($prevPost) : null,
            'nextPost' => $nextPost ? $formatUrl($nextPost) : null,
            'landingUrl' => route('blog.public.landing', ['blog' => $blog->slug]),
            'isLandingPage' => false,
            'breadcrumbs' => [
                [
                    'label' => $appName,
                    'url' => $baseUrl,
                ],
                [
                    'label' => $blog->name,
                    'url' => route('blog.public.landing', ['blog' => $blog->slug]),
                ],
                [
                    'label' => $post->title,
                    'url' => null,
                ],
            ],
        ];
    }

    /**
     * Get the next or previous post relative to the current one.
     *
     * @param string $direction 'next' (older) or 'previous' (newer)
     */
    private function getAdjacentPost(Blog $blog, Post $post, string $direction): ?Post
    {
        $query = $blog->posts()
            ->published()
            ->public()
            ->select(['id', 'title', 'slug', 'published_at', 'created_at']);

        $compare = $direction === 'next' ? '<' : '>';
        $order = $direction === 'next' ? 'desc' : 'asc';

        return $query
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

    /**
     * Generate meta description from post content.
     */
    private function generatePostMetaDescription(string $contentHtml): string
    {
        $text = strip_tags($contentHtml);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        $limit = 160;
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $text = mb_substr($text, 0, $limit);
        $cutPosition = mb_strrpos($text, ' ');

        if ($cutPosition !== false && $cutPosition > 80) {
            $text = mb_substr($text, 0, $cutPosition);
        }

        return rtrim($text) . '…';
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

    /**
     * Generate structured data for single post page.
     */
    private function generatePostStructuredData(Blog $blog, Post $post, string $baseUrl, string $description): array
    {
        $blogUrl = $baseUrl . '/blogs/' . $blog->slug;
        $postUrl = $blogUrl . '/' . $post->slug;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $description,
            'url' => $postUrl,
            'datePublished' => optional($post->published_at)?->toIso8601String(),
            'dateModified' => optional($post->updated_at)?->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => $blog->name,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $blog->name,
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $postUrl,
            ],
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'item' => [
                            '@id' => $baseUrl,
                            'name' => config('app.name'),
                        ],
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'item' => [
                            '@id' => $blogUrl,
                            'name' => $blog->name,
                        ],
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'item' => [
                            '@id' => $postUrl,
                            'name' => $post->title,
                        ],
                    ],
                ],
            ],
        ];
    }

}
