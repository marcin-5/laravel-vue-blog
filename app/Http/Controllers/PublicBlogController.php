<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\LandingPage;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use ParsedownExtra;

class PublicBlogController extends Controller
{
    /**
     * Show the public landing page for a blog by slug.
     * Route: /{blog:slug}
     */
    public function landing(Request $request, Blog $blog): Response
    {
        $this->preparePublicBlog($blog);

        return Inertia::render('Blog/Landing', $this->getLandingViewProps($blog));
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
        $posts = $this->getPublicPosts($blog);
        $descriptionHtml = $this->parseMarkdownToHtml($blog->description);
        $metaDescription = $this->generateMetaDescription($blog, $descriptionHtml, $landing);

        return [
            'locale' => app()->getLocale(),
            'blog' => [
                'id' => $blog->id,
                'name' => $blog->name,
                'slug' => $blog->slug,
                'descriptionHtml' => $descriptionHtml,
            ],
            'landingHtml' => $landing?->content_html ?? '',
            'metaDescription' => $metaDescription,
            'posts' => $this->formatPostsForView($posts),
            'sidebarPosition' => $this->getSidebarPosition($blog),
        ];
    }

    /**
     * Get published, public posts for a blog.
     */
    private function getPublicPosts(Blog $blog): EloquentCollection
    {
        return $blog->posts()
            ->published()
            ->public()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get(['id', 'blog_id', 'title', 'slug', 'excerpt', 'published_at', 'created_at']);
    }

    /**
     * Parse Markdown to safe HTML.
     */
    private function parseMarkdownToHtml(?string $markdown): string
    {
        if (empty($markdown)) {
            return '';
        }

        $parser = new ParsedownExtra();
        if (method_exists($parser, 'setSafeMode')) {
            $parser->setSafeMode(true);
        }

        return $parser->text($markdown);
    }

    /**
     * Build plain-text meta description for SEO.
     */
    private function generateMetaDescription(Blog $blog, string $descriptionHtml, ?LandingPage $landing): string
    {
        $source = $descriptionHtml;
        if (empty($source)) {
            $source = $landing?->content_html ?? '';
        }
        if (empty($source)) {
            $source = $blog->name ?? '';
        }

        // Strip HTML tags, replacing them with spaces to avoid words merging.
        $text = trim(preg_replace('/<[^>]*>/', ' ', $source) ?? '');
        // Convert Markdown link/image leftovers if any
        $text = preg_replace('/!\[([^\]]*)\]\([^\)]+\)/', '$1', $text); // images -> alt
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text); // links -> text
        // Remove emphasis markers
        $text = preg_replace('/(\*\*|__|\*|_|`)/', '', $text);
        // Remove heading/blockquote/list markers at line starts
        $text = preg_replace('/^\s{0,3}[#>\-]+\s*/m', '', $text);
        // Collapse whitespace and decode HTML entities
        $text = Str::squish(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));

        // Truncate to ~160 chars without cutting words
        $limit = 160;
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $sliced = mb_substr($text, 0, $limit);

        // Find last space to avoid cutting words, but not too close to the beginning.
        $cut = mb_strrpos($sliced, ' ');
        $result = ($cut !== false && $cut > 80) ? mb_substr($sliced, 0, $cut) : $sliced;

        return rtrim($result) . '…';
    }

    /**
     * Format posts for the Inertia view.
     */
    private function formatPostsForView(EloquentCollection $posts): Collection
    {
        return $posts->map(fn(Post $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'excerpt' => $p->excerpt,
            'published_at' => optional($p->published_at)->toDateString(),
        ])->values();
    }

    /**
     * Determine sidebar placement from landing page settings.
     */
    private function getSidebarPosition(Blog $blog): string
    {
        return $blog->landingPage?->sidebar_position ?? LandingPage::SIDEBAR_NONE;
    }

    /**
     * Show a single post by slug for a blog by slug.
     * Route: /{blog:slug}/{post:slug}
     */
    public function post(Request $request, Blog $blog, string $postSlug): Response
    {
        $this->preparePublicBlog($blog);
        $post = $this->getPublicPost($blog, $postSlug);

        return Inertia::render('Blog/Post', $this->getPostViewProps($blog, $post));
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
        return [
            'locale' => app()->getLocale(),
            'blog' => [
                'id' => $blog->id,
                'name' => $blog->name,
                'slug' => $blog->slug,
            ],
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'contentHtml' => $post->content_html,
                'published_at' => optional($post->published_at)?->toDayDateTimeString(),
            ],
            'posts' => $this->formatPostsForView($this->getPublicPosts($blog)),
            'sidebarPosition' => $this->getSidebarPosition($blog),
        ];
    }
}
