<?php

namespace App\Http\Middleware;

use App\Models\Blog;
use App\Models\MarkdownView;
use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class TrackMarkdownRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = $response->headers->get('Content-Type', '');

        if (str_contains($contentType, 'text/markdown')) {
            $this->logMarkdownRequest($request);
        }

        return $response;
    }

    protected function logMarkdownRequest(Request $request): void
    {
        /** @var Route|null $route */
        $route = $request->route();

        if (!$route) {
            return;
        }

        $name = $route->getName();
        $viewable = null;

        if ($name === 'blog.public.landing') {
            /** @var Blog|null $blog */
            $blog = $route->parameter('blog');
            if ($blog instanceof Blog) {
                $viewable = $blog;
            }
        }

        if ($name === 'blog.public.post') {
            /** @var Blog|null $blog */
            $blog = $route->parameter('blog');
            $postSlug = $route->parameter('postSlug');

            if ($blog instanceof Blog && is_string($postSlug)) {
                $post = $blog->posts()->where('slug', $postSlug)->first();
                if ($post instanceof Post) {
                    $viewable = $post;
                }
            }
        }

        if ($viewable) {
            $view = MarkdownView::firstOrNew([
                'viewable_type' => $viewable->getMorphClass(),
                'viewable_id' => $viewable->getKey(),
                'ip_address' => $request->ip(),
            ]);

            if ($view->exists) {
                $view->hits++;
            } else {
                $view->hits = 1;
            }

            $view->user_agent = substr((string)$request->header('User-Agent', ''), 0, 255);
            $view->last_seen_at = now();
            $view->save();
        }
    }
}
