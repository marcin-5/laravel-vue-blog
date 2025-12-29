<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\PageView;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $newsletterSubscriptions = [];
        $blogStats = [];

        if ($user->isAdmin()) {
            $newsletterSubscriptions = NewsletterSubscription::with('blog')
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

        if ($user->isBlogger() || $user->isAdmin()) {
            $blogStats = Blog::query()
                ->where('user_id', $user->id)
                ->withCount('posts')
                ->withCount([
                    'newsletterSubscriptions as daily_subscriptions_count' => function ($query) {
                        $query->where('frequency', 'daily');
                    }
                ])
                ->withCount([
                    'newsletterSubscriptions as weekly_subscriptions_count' => function ($query) {
                        $query->where('frequency', 'weekly');
                    }
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
                        'total_views' => $totalViews,
                        'daily_subscriptions_count' => $blog->daily_subscriptions_count,
                        'weekly_subscriptions_count' => $blog->weekly_subscriptions_count,
                    ];
                });
        }

        return Inertia::render('app/Dashboard', [
            'newsletterSubscriptions' => $newsletterSubscriptions,
            'blogStats' => $blogStats,
        ]);
    }
}
