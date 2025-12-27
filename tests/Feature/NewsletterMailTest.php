<?php

use App\Mail\NewsletterPostNotification;
use App\Models\Blog;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the newsletter email without errors', function () {
    $blog = Blog::factory()->create();
    $posts = Post::factory()->count(1)->create([
        'blog_id' => $blog->id,
    ]);

    $mailable = new NewsletterPostNotification($blog, $posts);

    $mailable->assertSeeInHtml($posts->first()->title);
    $mailable->assertDontSeeInHtml('<code>');
    $mailable->assertDontSeeInHtml('<pre>');
});
