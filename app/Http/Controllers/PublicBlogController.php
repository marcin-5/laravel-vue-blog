<?php

namespace App\Http\Controllers;

use App\Actions\SubmitContactFormAction;
use App\Builders\PublicBlogPagePayloadBuilder;
use App\Builders\PublicBlogSeoBuilder;
use App\Http\Controllers\Concerns\FormatsDatesForLocale;
use App\Http\Controllers\Concerns\HandlesViewStats;
use App\Http\Middleware\SetLocale;
use App\Http\Requests\ContactSubmitRequest;
use App\Http\Resources\PublicBlogDetailResource;
use App\Http\Resources\PublicBlogResource;
use App\Http\Resources\PublicPostDetailResource;
use App\Models\Blog;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Thread;
use App\Queries\Public\PublicBlogPostsQuery;
use App\Services\BlogNavigationService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use App\Services\StatsService;
use App\Services\TranslationService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicBlogController extends BasePublicController
{
    use FormatsDatesForLocale, HandlesViewStats;

    public function __construct(
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
        private readonly PublicBlogSeoBuilder $seoBuilder,
        private readonly PublicBlogPagePayloadBuilder $payloadBuilder,
        private readonly BlogNavigationService $navigation,
        private readonly StatsService $stats,
        private readonly SubmitContactFormAction $submitAction,
        protected TranslationService $translations,
    ) {
        parent::__construct($translations);
    }

    /**
     * Show the public landing page for a blog by slug.
     * Route: /{blog:slug}
     * @throws FileNotFoundException
     */
    public function landing(Request $request, Blog $blog, string $mainDomain, PublicBlogPostsQuery $query): Response
    {
        $this->ensureBlogIsPublic($blog);
        $blog->load(['landingPage', 'user']);

        $activeTags = $this->resolveActiveTags($request, $blog);
        $operator = $this->resolveTagOperator($request, $blog);
        $paginator = $query->handle($blog, $activeTags, $operator);

        return $this->renderLandingPage($blog, $paginator, $activeTags, $operator);
    }

    /**
     * Ensure blog is published and set locale.
     */
    private function ensureBlogIsPublic(Blog $blog): void
    {
        abort_unless($blog->is_published, 404);

        $locale = $blog->locale ?? config('app.locale');
        app()->setLocale($locale);

        config([
            'app.name' => SetLocale::getAppNameForLocale($locale),
        ]);
    }

    /**
     * @return Collection<int, Tag>
     */
    private function resolveActiveTags(
        Request $request,
        Blog $blog,
        ?Tag $routeTag = null,
        bool $includeLegacyQueryTag = false,
    ): Collection {
        $slugs = [];

        if ($blog->allow_multiple_tag_filtering && is_array($request->query('tags'))) {
            $slugs = array_values(
                array_filter(
                    $request->query('tags'),
                    static fn(mixed $slug): bool => is_string($slug) && $slug !== '' && strlen($slug) <= 255,
                ),
            );
        }

        if ($includeLegacyQueryTag && is_string($request->query('tag')) && $request->query('tag') !== '') {
            $slugs[] = $request->query('tag');
        }

        if ($routeTag !== null) {
            $slugs[] = $routeTag->slug;
        }

        $slugs = array_values(array_unique($slugs));
        if ($slugs === []) {
            return new Collection;
        }

        $tagsBySlug = $blog->tags()->whereIn('slug', $slugs)->get()->keyBy('slug');

        return new Collection(
            array_values(
                array_filter(
                    array_map(
                        static fn(string $slug): ?Tag => $tagsBySlug->get($slug),
                        $slugs,
                    ),
                ),
            ),
        );
    }

    private function resolveTagOperator(Request $request, Blog $blog): string
    {
        return $blog->allow_multiple_tag_filtering && strtoupper((string) $request->query('operator')) === 'OR'
            ? 'OR'
            : 'AND';
    }

    /**
     * @param  Collection<int, Tag>  $activeTags
     * @throws FileNotFoundException
     */
    private function renderLandingPage(
        Blog $blog,
        LengthAwarePaginator $paginator,
        Collection $activeTags,
        string $operator,
        ?Tag $routeTag = null,
    ): Response {
        $landingHtml = $blog->landingPage?->content_html ?? '';

        if (!$blog->allow_multiple_tag_filtering && $activeTags->count() === 1) {
            $tagLandingPage = $activeTags->first()->landingPage;

            if (trim($tagLandingPage?->content ?? '') !== '') {
                $landingHtml = $tagLandingPage->content_html;
            }
        }

        return $this->renderWithTranslations('public/blog/Landing', 'blog', [
            'blog' => new PublicBlogDetailResource($blog),
            'landingHtml' => $landingHtml,
            'chrome' => $this->payloadBuilder->buildChrome(
                $blog,
                $this->navigation->getLandingNavigation($blog, $routeTag, $activeTags->all(), $operator),
            )->toArray(),
            'listing' => $this->payloadBuilder->buildListing($blog, $paginator, $activeTags, $operator)->toArray(),
            'seo' => $this->seoBuilder->buildLandingSeo(
                $blog,
                $paginator,
                $this->buildLandingMetaDescription($blog),
                $routeTag,
                $activeTags->map(static fn(Tag $tag): array => [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ])->all(),
                $operator,
            )->toArray(),
            'viewStats' => Inertia::defer(fn() => $this->getViewStats(Blog::class, $blog->id, $blog->user_id)),
        ]);
    }

    private function buildLandingMetaDescription(Blog $blog): string
    {
        $descriptionHtml = str_replace('-!-', '', $this->markdown->convertToHtml($blog->description));

        return $blog->seo_description ?: $this->seo->generateMetaDescription(
            $descriptionHtml ?: $blog->landingPage?->content_html ?: $blog->name,
        );
    }

    /**
     * Show a single post by slug for a blog by slug.
     * Route: /{blog:slug}/{post:slug}
     *
     * @throws ModelNotFoundException
     * @throws FileNotFoundException
     */
    public function post(
        Request $request,
        Blog $blog,
        string $mainDomain,
        string $postSlug,
        PublicBlogPostsQuery $query,
    ): Response {
        $this->ensureBlogIsPublic($blog);

        $tag = is_string($request->query('tag'))
            ? $blog->tags()->where('slug', $request->query('tag'))->first()
            : null;
        $activeTags = $this->resolveActiveTags($request, $blog, $tag, includeLegacyQueryTag: true);
        $operator = $this->resolveTagOperator($request, $blog);

        $post = $blog
            ->posts()
            ->findBySlugForPublic($postSlug)
            ->with([
                'user',
                'extensions' => fn($q) => $q->where('is_published', true)->oldest(),
                'relatedPosts' => fn($q) => $q->orderBy('display_order'),
                'relatedPosts.relatedPost.blog',
                'externalLinks' => fn($q) => $q->orderBy('display_order'),
                'threads' => function ($q) use ($request): void {
                    $q->with('user')->withCount('comments')->latest()->latest('id');

                    if ($request->user() === null) {
                        $q->where('visibility', Thread::VIS_PUBLIC);
                    }
                },
            ])
            ->firstOrFail();

        $paginator = $query->handle($blog, $activeTags, $operator);
        $paginator->setPath(route('blog.public.landing', ['blog' => $blog->slug, 'mainDomain' => $mainDomain]));

        $metaDescription = $post->excerpt ?: $this->seo->generateMetaDescription($post->content_html);

        return $this->renderWithTranslations('public/blog/Post', 'post', [
            'blog' => new PublicBlogResource($blog),
            'post' => new PublicPostDetailResource($post),
            'chrome' => $this->payloadBuilder->buildChrome(
                $blog,
                $this->navigation->getPostNavigation($blog, $post, $tag, $activeTags->all(), $operator),
                withFooter: false,
            )->toArray(),
            'listing' => $this->payloadBuilder->buildListing($blog, $paginator, $activeTags, $operator)->toArray(),
            'seo' => $this->seoBuilder->buildPostSeo($blog, $post, $metaDescription)->toArray(),
            'viewStats' => Inertia::defer(fn() => $this->getViewStats(Post::class, $post->id, $blog->user_id)),
        ]);
    }

    /**
     * Show posts filtered by tag within a blog.
     * Route: /{blog:slug}/tags/{tag:slug}
     * @throws FileNotFoundException
     */
    public function tag(
        Request $request,
        Blog $blog,
        string $mainDomain,
        Tag $tag,
        PublicBlogPostsQuery $query,
    ): Response {
        $this->ensureBlogIsPublic($blog);

        // Ensure tag belongs to the same blog
        abort_unless($tag->blog_id === $blog->id, 404);

        $blog->load(['landingPage', 'user']);

        $activeTags = $this->resolveActiveTags($request, $blog, $tag);
        $operator = $this->resolveTagOperator($request, $blog);
        $paginator = $query->handle($blog, $activeTags, $operator);

        return $this->renderLandingPage($blog, $paginator, $activeTags, $operator, $tag);
    }

    /**
     * Show the about page for a blog.
     * Route: /about
     * @throws FileNotFoundException
     */
    public function about(Request $request, Blog $blog, string $mainDomain): Response
    {
        $this->ensureBlogIsPublic($blog);
        $blog->load(['landingPage', 'user']);

        return $this->renderWithTranslations('public/blog/About', 'about', [
            'blog' => new PublicBlogDetailResource($blog),
            'chrome' => $this->payloadBuilder->buildChrome(
                $blog,
                $this->navigation->getLandingNavigation($blog),
            )->toArray(),
            'seo' => $this->seoBuilder->buildAboutSeo($blog)->toArray(),
        ]);
    }

    /**
     * Show the contact page for a blog.
     * Route: /contact
     * @throws FileNotFoundException
     */
    public function contact(Request $request, Blog $blog, string $mainDomain): Response
    {
        $this->ensureBlogIsPublic($blog);
        $blog->load(['landingPage', 'user']);

        return $this->renderWithTranslations('public/blog/Contact', 'contact', [
            'blog' => new PublicBlogDetailResource($blog),
            'chrome' => $this->payloadBuilder->buildChrome(
                $blog,
                $this->navigation->getLandingNavigation($blog),
            )->toArray(),
            'seo' => $this->seoBuilder->buildContactSeo($blog)->toArray(),
            'recipientName' => $blog->user->name,
            'submitUrl' => route('blog.public.contact.submit', ['blog' => $blog->slug, 'mainDomain' => $mainDomain]),
        ]);
    }

    /**
     * Handle contact form submission for a blog.
     * Route: POST /contact
     */
    public function submitContact(ContactSubmitRequest $request, Blog $blog, string $mainDomain): JsonResponse
    {
        $this->ensureBlogIsPublic($blog);
        $blog->load(['user']);

        $this->submitAction->execute(
            $request->validated(),
            $blog->user->email,
            $blog->user->name,
        );

        return response()->json([
            'message' => 'Thanks for your message. We will get back to you soon.',
        ]);
    }
}
