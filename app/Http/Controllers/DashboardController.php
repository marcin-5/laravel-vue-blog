<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\PageView;
use App\Models\Post;
use App\Models\UserAgent;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected TranslationService $translations)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $newsletterSubscriptions = [];
        $blogStats = [];
        $postsStats = [];
        $userAgentStats = null;

        if ($user->isAdmin()) {
            $newsletterSubscriptions = $this->getNewsletterSubscriptions();
            $userAgentStats = [
                'last_unique' => $this->getLastUniqueUserAgents(),
                'last_added' => $this->getLastAddedUserAgents(),
            ];
        }

        if ($user->isBlogger() || $user->isAdmin()) {
            $blogStats = $this->getBlogStats($user);
            $postsStats = $this->getPostsStats($user);
        }

        return Inertia::render('app/Dashboard', [
            'newsletterSubscriptions' => $newsletterSubscriptions,
            'blogStats' => $blogStats,
            'postsStats' => $postsStats,
            'userAgentStats' => $userAgentStats,
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('dashboard'),
            ],
        ]);
    }

    private function getNewsletterSubscriptions()
    {
        return NewsletterSubscription::with('blog')
            ->latest()
            ->get()
            ->groupBy('email')
            ->take(5)
            ->map(function ($group, $email) {
                return [
                    'email' => $email,
                    'subscriptions' => $group->map(function ($sub) {
                        return [
                            'blog' => $sub->blog->name,
                            'frequency' => $sub->frequency,
                        ];
                    })->values()->all(),
                ];
            })
            ->values();
    }

    private function getLastUniqueUserAgents()
    {
        return PageView::query()
            ->whereNotNull('user_agent_id')
            ->with('userAgent')
            ->latest()
            ->get()
            ->unique('user_agent_id')
            ->take(5)
            ->map(fn($pv) => [
                'id' => $pv->userAgent->id,
                'name' => $pv->userAgent->name,
            ])
            ->values();
    }

    private function getLastAddedUserAgents()
    {
        return UserAgent::query()
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($ua) => [
                'id' => $ua->id,
                'name' => $ua->name,
            ]);
    }

    private function getBlogStats($user)
    {
        return Blog::query()
            ->where('user_id', $user->id)
            ->withCount('posts')
            ->withCount([
                'newsletterSubscriptions as daily_subscriptions_count' => function ($query) {
                    $query->where('frequency', 'daily');
                },
            ])
            ->withCount([
                'newsletterSubscriptions as weekly_subscriptions_count' => function ($query) {
                    $query->where('frequency', 'weekly');
                },
            ])
            ->get()
            ->map(function (Blog $blog) {
                $postMorphClass = (new Post)->getMorphClass();
                $totalViews = PageView::query()
                    ->where('viewable_type', $postMorphClass)
                    ->whereIn('viewable_id', $blog->posts()->pluck('id'))
                    ->count();

                return [
                    'id' => $blog->id,
                    'name' => $blog->name,
                    'posts_count' => $blog->posts_count,
                    'lifetime_views' => $totalViews,
                    'daily_subscriptions_count' => $blog->daily_subscriptions_count,
                    'weekly_subscriptions_count' => $blog->weekly_subscriptions_count,
                ];
            });
    }

    private function getPostsStats($user)
    {
        $posts = Post::query()
            ->whereIn('blog_id', $user->blogs()->pluck('id'))
            ->get();

        return [
            'timeline' => $posts->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'published_at' => $post->published_at?->toIso8601String() ?? $post->created_at->toIso8601String(),
                    'views' => [
                        'total' => $post->pageViews()->count(),
                        'year' => $post->pageViews()->where('created_at', '>=', now()->subYear())->count(),
                        'half_year' => $post->pageViews()->where('created_at', '>=', now()->subMonths(6))->count(),
                        'month' => $post->pageViews()->where('created_at', '>=', now()->subMonth())->count(),
                        'week' => $post->pageViews()->where('created_at', '>=', now()->subWeek())->count(),
                        'day' => $post->pageViews()->where('created_at', '>=', now()->subDay())->count(),
                    ],
                ];
            })->sortByDesc('published_at')->values(),
            'performance' => $posts->map(function (Post $post) {
                $publishedAt = $post->published_at ?? $post->created_at;
                $daysSincePublished = (int)max(1, abs(now()->diffInDays($publishedAt)));
                $views = $post->pageViews()->count();

                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'ratio' => round($views / $daysSincePublished, 2),
                ];
            })->sortByDesc('ratio')->values(),
        ];
    }
}
