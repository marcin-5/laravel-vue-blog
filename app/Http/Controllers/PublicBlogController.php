<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\LandingPage;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicBlogController extends Controller
{
    /**
     * Show the public landing page for a blog by slug.
     * Route: /{blog:slug}
     */
    public function landing(Request $request, Blog $blog): Response
    {
        // Only allow published blogs to be visible publicly
        abort_unless($blog->is_published, 404);

        // Set application locale based on blog's locale for SSR and translations
        app()->setLocale($blog->locale ?? config('app.locale'));

        // Load landing page (optional)
        $landing = $blog->landingPage;

        // Load list of posts: published and public
        $posts = $blog->posts()
            ->published()
            ->public()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get(['id', 'blog_id', 'title', 'slug', 'excerpt', 'published_at', 'created_at']);

        // Determine sidebar placement
        $sidebarPosition = $landing?->sidebar_position ?? LandingPage::SIDEBAR_NONE;

        return Inertia::render('Blog/Landing', [
            'locale' => $blog->locale ?? config('app.locale'),
            'blog' => [
                'id' => $blog->id,
                'name' => $blog->name,
                'slug' => $blog->slug,
                'description' => $blog->description,
            ],
            'landingHtml' => $landing?->content_html ?? '',
            'posts' => $posts->map(fn (Post $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'excerpt' => $p->excerpt,
                'published_at' => optional($p->published_at)->toDateString(),
            ])->values(),
            'sidebarPosition' => $sidebarPosition,
        ]);
    }

    /**
     * Show a single post by slug for a blog by slug.
     * Route: /{blog:slug}/{post:slug}
     */
    public function post(Request $request, Blog $blog, string $postSlug): Response
    {
        // Only allow published blogs to be visible publicly
        abort_unless($blog->is_published, 404);

        // Set application locale based on blog's locale for SSR and translations
        app()->setLocale($blog->locale ?? config('app.locale'));

        // Find the post within this blog that is public and published
        $post = $blog->posts()
            ->where('slug', $postSlug)
            ->published()
            ->public()
            ->firstOrFail();

        // Load sidebar settings from landing page if exists
        $landing = $blog->landingPage; // optional, used to determine sidebar
        $sidebarPosition = $landing?->sidebar_position ?? LandingPage::SIDEBAR_NONE;

        // Load list of posts for sidebar/list
        $posts = $blog->posts()
            ->published()
            ->public()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get(['id', 'blog_id', 'title', 'slug', 'excerpt', 'published_at', 'created_at']);

        return Inertia::render('Blog/Post', [
            'locale' => $blog->locale ?? config('app.locale'),
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
            'posts' => $posts->map(fn (Post $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'excerpt' => $p->excerpt,
                'published_at' => optional($p->published_at)->toDateString(),
            ])->values(),
            'sidebarPosition' => $sidebarPosition,
        ]);
    }
}
