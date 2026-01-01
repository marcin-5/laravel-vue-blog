<?php

use App\Mail\NewsletterPostNotification;
use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the newsletter email without errors', function () {
    $blog = Blog::factory()->create();
    $subscription = NewsletterSubscription::factory()->create([
        'blog_id' => $blog->id,
    ]);
    $posts = Post::factory()->count(1)->create([
        'blog_id' => $blog->id,
    ]);

    $data = collect([
        [
            'subscription' => $subscription,
            'blog' => $blog,
            'posts' => $posts,
        ],
    ]);

    $mailable = new NewsletterPostNotification($data);

    $mailable->assertSeeInHtml($posts->first()->title);
    $mailable->assertDontSeeInHtml('<code>');
    $mailable->assertDontSeeInHtml('<pre>');
});
