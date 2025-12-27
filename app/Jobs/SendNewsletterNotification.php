<?php

namespace App\Jobs;

use App\Mail\NewsletterPostNotification;
use App\Models\NewsletterLog;
use App\Models\NewsletterSubscription;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendNewsletterNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param Collection<int, Post> $posts
     */
    public function __construct(
        public NewsletterSubscription $subscription,
        public Collection $posts,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->posts->isEmpty()) {
            return;
        }

        Mail::to($this->subscription->email)->send(
            new NewsletterPostNotification($this->subscription->blog, $this->posts),
        );

        $sentAt = now();
        foreach ($this->posts as $post) {
            NewsletterLog::query()->updateOrCreate(
                [
                    'newsletter_subscription_id' => $this->subscription->id,
                    'post_id' => $post->id,
                ],
                [
                    'sent_at' => $sentAt,
                ],
            );
        }
    }
}
