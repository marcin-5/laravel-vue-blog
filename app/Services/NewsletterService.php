<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NewsletterSubscription;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class NewsletterService
{
    /**
     * Update existing subscriptions for an email. Removes unselected and adds/updates others.
     *
     * @param  string  $email
     * @param  array<int, array{blog_id: int, frequency: string, send_time?: string|null, send_time_weekend?: string|null, send_day?: int|null}>  $subscriptions
     * @param  string|null  $visitorId
     * @return void
     * @throws Throwable
     */
    public function updateSubscriptions(string $email, array $subscriptions, ?string $visitorId = null): void
    {
        DB::transaction(function () use ($email, $subscriptions, $visitorId) {
            $blogIds = collect($subscriptions)->pluck('blog_id');

            NewsletterSubscription::query()
                ->where('email', $email)
                ->whereNotIn('blog_id', $blogIds)
                ->delete();

            $this->persistSubscriptions($email, $subscriptions, $visitorId);
        });
    }

    /**
     * Subscribe an email to one or more blogs.
     *
     * @param  string  $email
     * @param  array<int, array{blog_id: int, frequency: string, send_time?: string|null, send_time_weekend?: string|null, send_day?: int|null}>  $subscriptions
     * @param  string|null  $visitorId
     * @return void
     * @throws Throwable
     */
    public function subscribe(string $email, array $subscriptions, ?string $visitorId = null): void
    {
        DB::transaction(function () use ($email, $subscriptions, $visitorId) {
            $this->persistSubscriptions($email, $subscriptions, $visitorId);
        });
    }

    /**
     * @param array<int, array{blog_id: int, frequency: string, send_time?: string|null, send_time_weekend?: string|null, send_day?: int|null}> $subscriptions
     */
    private function persistSubscriptions(string $email, array $subscriptions, ?string $visitorId): void
    {
        foreach ($subscriptions as $subscription) {
            NewsletterSubscription::query()->updateOrCreate(
                [
                    'email' => $email,
                    'blog_id' => $subscription['blog_id'],
                ],
                [
                    'frequency' => $subscription['frequency'],
                    'send_time' => $subscription['send_time'] ?? null,
                    'send_time_weekend' => $subscription['send_time_weekend'] ?? null,
                    'send_day' => $subscription['send_day'] ?? null,
                    'visitor_id' => $visitorId,
                ],
            );
        }
    }

    /**
     * Remove all subscriptions for a given email.
     *
     * @param  string  $email
     * @return void
     */
    public function unsubscribe(string $email): void
    {
        NewsletterSubscription::query()->where('email', $email)->delete();
    }
}
