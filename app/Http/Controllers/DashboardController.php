<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BotView;
use App\Models\NewsletterSubscription;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Models\UserAgent;
use App\Services\TranslationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
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
        $dashboardData = $this->prepareDashboardData($user);

        return Inertia::render('app/Dashboard', [
            ...$dashboardData,
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('dashboard'),
            ],
        ]);
    }

    private function prepareDashboardData(User $user): array
    {
        $data = [
            'newsletterSubscriptions' => [],
            'blogStats' => [],
            'postsStats' => [],
            'userAgentStats' => null,
            'botStats' => null,
        ];

        if ($user->isAdmin()) {
            $data['newsletterSubscriptions'] = $this->getNewsletterSubscriptions();
            $data['userAgentStats'] = $this->getUserAgentStats();
            $data['botStats'] = $this->getBotStats();
        }

        if ($user->isBlogger() || $user->isAdmin()) {
            $data['blogStats'] = $this->getBlogStats($user);
            $data['postsStats'] = $this->getPostsStats($user);
        }

        return $data;
    }

    private function getNewsletterSubscriptions(): SupportCollection
    {
        $subscriptions = NewsletterSubscription::with('blog')
            ->latest()
            ->get()
            ->groupBy('email')
            ->take(5);

        return $subscriptions->map(fn($group, $email) => [
            'email' => $email,
            'subscriptions' => $group->map(fn($sub) => [
                'blog' => $sub->blog->name,
                'frequency' => $sub->frequency,
            ])->values()->all(),
        ])->values();
    }

    private function getUserAgentStats(): array
    {
        return [
            'last_unique' => $this->getLastUniqueUserAgents(),
            'last_added' => $this->getLastAddedUserAgents(),
        ];
    }

    private function getLastUniqueUserAgents(): SupportCollection
    {
        return PageView::query()
            ->whereNotNull('user_agent_id')
            ->with('userAgent')
            ->latest()
            ->get()
            ->unique('user_agent_id')
            ->take(5)
            ->map(fn($pageView) => [
                'id' => $pageView->userAgent->id,
                'name' => $pageView->userAgent->name,
            ])
            ->values();
    }

    private function getLastAddedUserAgents(): SupportCollection
    {
        return UserAgent::query()
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($userAgent) => [
                'id' => $userAgent->id,
                'name' => $userAgent->name,
            ]);
    }

    private function getBotStats(): array
    {
        return [
            'last_seen' => $this->getLastBotViews(),
            'top_hits' => $this->getMostActiveBots(),
            'total_hits' => (int)BotView::query()->sum('hits'),
        ];
    }

    private function getLastBotViews(): SupportCollection
    {
        $fragments = config('bots.fragments', []);
        // Sort fragments by length descending to match most specific ones first (e.g. 'googlebot' before 'bot')
        $sortedFragments = collect($fragments)
            ->sortByDesc(fn($f) => strlen((string)$f))
            ->values()
            ->all();

        return BotView::query()
            ->with('userAgent')
            ->latest('last_seen_at')
            ->get()
            ->unique('user_agent_id')
            ->take(5)
            ->map(function (BotView $botView) use ($sortedFragments) {
                $userAgentName = $botView->userAgent->name;
                $matchedFragment = collect($sortedFragments)->first(
                    fn($f) => stripos($userAgentName, (string)$f) !== false,
                );

                return [
                    'id' => $botView->userAgent->id,
                    'name' => $userAgentName,
                    'matched_fragment' => $matchedFragment ?? $userAgentName,
                    'hits' => $botView->hits,
                    'last_seen_at' => $botView->last_seen_at->toIso8601String(),
                ];
            })
            ->values();
    }

    private function getMostActiveBots(): SupportCollection
    {
        $fragments = config('bots.fragments', []);
        // Sort fragments by length descending to match most specific ones first (e.g. 'googlebot' before 'bot')
        $sortedFragments = collect($fragments)
            ->sortByDesc(fn($f) => strlen((string)$f))
            ->values()
            ->all();

        return BotView::query()
            ->selectRaw('user_agent_id, SUM(hits) as total_hits, MAX(last_seen_at) as last_seen_at')
            ->groupBy('user_agent_id')
            ->orderByDesc('total_hits')
            ->with('userAgent')
            ->take(5)
            ->get()
            ->map(function ($botView) use ($sortedFragments) {
                $userAgentName = $botView->userAgent->name;
                $matchedFragment = collect($sortedFragments)->first(
                    fn($f) => stripos($userAgentName, (string)$f) !== false,
                );

                return [
                    'id' => $botView->userAgent->id,
                    'name' => $userAgentName,
                    'matched_fragment' => $matchedFragment ?? $userAgentName,
                    'hits' => (int)$botView->total_hits,
                    'last_seen_at' => $botView->last_seen_at->toIso8601String(),
                ];
            });
    }

    private function getBlogStats(User $user): SupportCollection
    {
        $blogs = Blog::query()
            ->where('user_id', $user->id)
            ->withCount('posts')
            ->withCount([
                'newsletterSubscriptions as daily_subscriptions_count' => fn($query) => $query->where(
                    'frequency',
                    'daily',
                ),
                'newsletterSubscriptions as weekly_subscriptions_count' => fn($query) => $query->where(
                    'frequency',
                    'weekly',
                ),
            ])
            ->get();

        $postMorphClass = (new Post)->getMorphClass();
        $viewCounts = $this->getBlogViewCounts($blogs, $postMorphClass);

        return $blogs->map(fn(Blog $blog) => [
            'id' => $blog->id,
            'name' => $blog->name,
            'posts_count' => $blog->posts_count,
            'lifetime_views' => $viewCounts[$blog->id] ?? 0,
            'daily_subscriptions_count' => $blog->daily_subscriptions_count,
            'weekly_subscriptions_count' => $blog->weekly_subscriptions_count,
        ]);
    }

    private function getBlogViewCounts(Collection $blogs, string $postMorphClass): array
    {
        $postIds = Post::query()
            ->whereIn('blog_id', $blogs->pluck('id'))
            ->pluck('id', 'blog_id')
            ->groupBy(fn($postId, $blogId) => $blogId);

        $viewCounts = [];
        foreach ($postIds as $blogId => $blogPostIds) {
            $viewCounts[$blogId] = PageView::query()
                ->where('viewable_type', $postMorphClass)
                ->whereIn('viewable_id', $blogPostIds)
                ->count();
        }

        return $viewCounts;
    }

    private function getPostsStats(User $user): array
    {
        $posts = Post::query()
            ->whereIn('blog_id', $user->blogs()->pluck('id'))
            ->with('pageViews')
            ->get();

        $timelineData = $this->buildPostTimeline($posts);
        $performanceData = $this->buildPostPerformance($posts);

        return [
            'timeline' => $timelineData,
            'performance' => $performanceData,
        ];
    }

    private function buildPostTimeline(Collection $posts): SupportCollection
    {
        return $posts->map(function (Post $post) {
            $viewsByPeriod = $this->calculateViewsByPeriod($post->pageViews);

            return [
                'id' => $post->id,
                'title' => $post->title,
                'published_at' => $post->published_at?->toIso8601String()
                    ?? $post->created_at->toIso8601String(),
                'views' => $viewsByPeriod,
            ];
        })->sortByDesc('published_at')->values();
    }

    private function calculateViewsByPeriod(Collection $pageViews): array
    {
        $now = now();

        return [
            'total' => $pageViews->count(),
            'year' => $pageViews->where('created_at', '>=', $now->copy()->subYear())->count(),
            'half_year' => $pageViews->where('created_at', '>=', $now->copy()->subMonths(6))->count(),
            'month' => $pageViews->where('created_at', '>=', $now->copy()->subMonth())->count(),
            'week' => $pageViews->where('created_at', '>=', $now->copy()->subWeek())->count(),
            'day' => $pageViews->where('created_at', '>=', $now->copy()->subDay())->count(),
        ];
    }

    private function buildPostPerformance(Collection $posts): SupportCollection
    {
        return $posts->map(function (Post $post) {
            $publishedAt = $post->published_at ?? $post->created_at;
            $daysSincePublished = (int)max(1, abs(now()->diffInDays($publishedAt)));
            $totalViews = $post->pageViews->count();

            return [
                'id' => $post->id,
                'title' => $post->title,
                'ratio' => round($totalViews / $daysSincePublished, 2),
            ];
        })->sortByDesc('ratio')->values();
    }
}
