<?php

namespace App\Jobs;

use App\Mail\NewsletterPostNotification;
use App\Models\Blog;
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
     * @param Collection<int, array{subscription: NewsletterSubscription, blog: Blog, posts: Collection<int, Post>}> $data
     */
    public function __construct(
        public string $email,
        public Collection $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->data->isEmpty()) {
            return;
        }

        Mail::to($this->email)->send(
            new NewsletterPostNotification($this->data),
        );

        $sentAt = now();
        foreach ($this->data as $item) {
            foreach ($item['posts'] as $post) {
                NewsletterLog::query()->updateOrCreate(
                    [
                        'newsletter_subscription_id' => $item['subscription']->id,
                        'post_id' => $post->id,
                    ],
                    [
                        'sent_at' => $sentAt,
                    ],
                );
            }
        }
    }
}
