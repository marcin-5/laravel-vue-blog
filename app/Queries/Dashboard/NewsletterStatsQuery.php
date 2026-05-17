<?php

declare(strict_types=1);

namespace App\Queries\Dashboard;

use App\Models\NewsletterSubscription;
use Illuminate\Support\Collection;

class NewsletterStatsQuery
{
    /**
     * Get the latest newsletter subscriptions grouped by email.
     *
     * @return Collection<int, array{email: string, subscriptions: list<array{blog: string, frequency: string}>}>
     */
    public function handle(): Collection
    {
        $latestEmails = NewsletterSubscription::query()
            ->select('email')
            ->groupBy('email')
            ->orderByRaw('MAX(created_at) DESC')
            ->limit(5)
            ->pluck('email');

        if ($latestEmails->isEmpty()) {
            return collect();
        }

        /** @var Collection<string, Collection<int, NewsletterSubscription>> $subscriptions */
        $subscriptions = NewsletterSubscription::with('blog:id,name')
            ->whereIn('email', $latestEmails)
            ->latest()
            ->get()
            ->groupBy('email');

        // Maintain the order from $latestEmails
        return $latestEmails->map(fn($email) => [
            'email' => $email,
            'subscriptions' => $subscriptions->get($email, collect())->map(fn($sub) => [
                'blog' => $sub->blog->name,
                'frequency' => $sub->frequency,
            ])->values()->all(),
        ]);
    }
}
