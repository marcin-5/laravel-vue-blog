<?php

namespace App\Console\Commands;

use App\Jobs\SendNewsletterNotification;
use App\Models\NewsletterSubscription;
use App\Models\Post;
use Illuminate\Console\Command;

class SendNewsletter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:send {frequency? : Frequency (daily or weekly)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a newsletter to subscribers with new posts.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $frequency = $this->argument('frequency');

        $subscriptionsQuery = NewsletterSubscription::query()->with(['blog']);

        if ($frequency) {
            $subscriptionsQuery->where('frequency', $frequency);
        }

        $subscriptions = $subscriptionsQuery->get();

        $this->info("Processing {$subscriptions->count()} subscriptions...");

        $activeSubscriptions = $subscriptions->filter(fn($subscription) => $this->shouldSendNow($subscription));

        $groupedSubscriptions = $activeSubscriptions->groupBy('email');

        foreach ($groupedSubscriptions as $email => $userSubscriptions) {
            $data = collect();

            foreach ($userSubscriptions as $subscription) {
                $posts = Post::query()
                    ->where('blog_id', $subscription->blog_id)
                    ->forPublicView()
                    ->whereDoesntHave('newsletterLogs', function ($query) use ($subscription) {
                        $query->where('newsletter_subscription_id', $subscription->id);
                    })
                    ->when($subscription->frequency === 'daily', function ($query) {
                        $query->where('published_at', '>=', now()->subDay());
                    })
                    ->when($subscription->frequency === 'weekly', function ($query) {
                        $query->where('published_at', '>=', now()->subWeek());
                    })
                    ->latest('published_at')
                    ->get();

                if ($posts->isNotEmpty()) {
                    $data->push([
                        'subscription' => $subscription,
                        'blog' => $subscription->blog,
                        'posts' => $posts,
                    ]);
                }
            }

            if ($data->isNotEmpty()) {
                dispatch(new SendNewsletterNotification($email, $data));
                $this->line("Consolidated newsletter queued for {$email} ({$data->count()} blogs).");
            }
        }

        $this->info('Newsletter processing has been completed.');
    }

    private function shouldSendNow(NewsletterSubscription $subscription): bool
    {
        $now = now();

        if ($subscription->frequency === 'daily') {
            $isWeekend = $now->isWeekend();

            if ($isWeekend) {
                $configTime = config('newsletter.daily_weekend_time', '11:11');
                $sendTime = $subscription->send_time_weekend ?? $configTime;
            } else {
                $configTime = config('newsletter.daily_weekday_time', '07:07');
                $sendTime = $subscription->send_time ?? $configTime;
            }

            return $now->format('H:i') === $sendTime;
        }

        if ($subscription->frequency === 'weekly') {
            $configDay = config('newsletter.weekly_day', 7);
            $configTime = config('newsletter.weekly_time', '19:19');

            $sendDay = $subscription->send_day ?? $configDay;
            $sendTime = $subscription->send_time ?? $configTime;

            return $now->dayOfWeekIso == $sendDay && $now->format('H:i') === $sendTime;
        }

        return false;
    }
}
