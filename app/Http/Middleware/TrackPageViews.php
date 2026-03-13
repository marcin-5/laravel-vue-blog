<?php

namespace App\Http\Middleware;

use App\Models\Blog;
use App\Models\Post;
use App\Services\PageViewTracker;
use Closure;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

use function is_string;

readonly class TrackPageViews
{
    public function __construct(
        private PageViewTracker $tracker,
    ) {}

    /**
     * @throws ClassMorphViolationException|InvalidArgumentException
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Route|null $route */
        $route = $request->route();

        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type', '');

        if (str_contains($contentType, 'text/markdown')) {
            return $response;
        }

        if (!$route) {
            return $response;
        }

        $name = $route->getName();

        // Blog landing: /{blog:slug}
        if ($name === 'blog.public.landing') {
            /** @var Blog|null $blog */
            $blog = $route->parameter('blog');
            if ($blog instanceof Blog) {
                $this->tracker->track($blog, $request);
            }
        }

        // Post: /{blog:slug}/{postSlug}
        if ($name === 'blog.public.post') {
            /** @var Blog|null $blog */
            $blog = $route->parameter('blog');
            $postSlug = $route->parameter('postSlug');

            if ($blog instanceof Blog && is_string($postSlug)) {
                // Use simple slug lookup without published_at check to avoid timing issues
                // with now() being called at different moments. The controller already
                // validated that the post is published before rendering the page.
                $post = $blog
                    ->posts()
                    ->where('slug', $postSlug)
                    ->first();

                if ($post instanceof Post) {
                    $this->tracker->track($post, $request);
                }
            }
        }

        return $response;
    }
}
