<?php

use App\Mail\NewsletterPostNotification;

it('renders the newsletter email without errors', function () {
    $blog = createBlog();
    $subscription = createSubscription($blog);
    $posts = collect([createPost($blog)]);

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
