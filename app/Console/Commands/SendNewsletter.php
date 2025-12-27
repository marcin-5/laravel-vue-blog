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

        $groupedSubscriptions = $subscriptions->groupBy('email');

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
                $this->line("Zakolejkowano skonsolidowany newsletter dla {$email} ({$data->count()} blogów).");
            }
        }

        $this->info('Zakończono przetwarzanie newslettera.');
    }
}
