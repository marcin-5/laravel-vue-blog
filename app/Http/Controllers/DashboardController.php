<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $newsletterSubscriptions = [];

        if ($request->user()->isAdmin()) {
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

        return Inertia::render('app/Dashboard', [
            'newsletterSubscriptions' => $newsletterSubscriptions,
        ]);
    }
}
