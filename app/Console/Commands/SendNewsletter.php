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
    protected $description = 'Wysyła newsletter do subskrybentów z nowymi wpisami.';

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

        $this->info("Przetwarzanie {$subscriptions->count()} subskrypcji...");

        foreach ($subscriptions as $subscription) {
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
                dispatch(new SendNewsletterNotification($subscription, $posts));
                $this->line("Zakolejkowano newsletter dla {$subscription->email} ({$posts->count()} nowych wpisów).");
            }
        }

        $this->info('Zakończono przetwarzanie newslettera.');
    }
}
