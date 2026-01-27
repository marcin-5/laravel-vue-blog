<?php

use App\Mail\NewsletterPostNotification;
use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

uses(RefreshDatabase::class);

it('contains translated newsletter content in polish', function () {
    App::setLocale('pl');

    $user = User::factory()->create();
    $blog = Blog::query()->create([
        'user_id' => $user->id,
        'name' => 'Testowy Blog',
        'slug' => 'testowy-blog',
        'is_published' => true,
        'locale' => 'pl',
    ]);
    $subscription = NewsletterSubscription::factory()->create([
        'email' => 'test@example.com',
        'blog_id' => $blog->id,
    ]);
    $posts = Post::factory()->count(2)->create(['blog_id' => $blog->id]);

    $data = collect([
        [
            'subscription' => $subscription,
            'blog' => $blog,
            'posts' => $posts,
        ],
    ]);

    $mailable = new NewsletterPostNotification($data);

    $mailable->assertSeeInHtml(__('newsletter.email.subject'));
    $mailable->assertSeeInHtml(__('newsletter.email.intro'));
    $mailable->assertSeeInHtml(__('newsletter.email.blog_prefix') . ' Testowy Blog');
    $mailable->assertSeeInHtml(__('newsletter.email.read_more'));
    $mailable->assertSeeInHtml(__('newsletter.email.thanks'));
    $mailable->assertSeeInHtml(__('newsletter.email.manage_subscription'));
    $mailable->assertSeeInHtml(__('newsletter.email.manage_link'));
});

it('contains translated newsletter content in english', function () {
    App::setLocale('en');

    $user = User::factory()->create();
    $blog = Blog::query()->create([
        'user_id' => $user->id,
        'name' => 'Test Blog',
        'slug' => 'test-blog',
        'is_published' => true,
        'locale' => 'en',
    ]);
    $subscription = NewsletterSubscription::factory()->create([
        'email' => 'test@example.com',
        'blog_id' => $blog->id,
    ]);
    $posts = Post::factory()->count(1)->create(['blog_id' => $blog->id]);

    $data = collect([
        [
            'subscription' => $subscription,
            'blog' => $blog,
            'posts' => $posts,
        ],
    ]);

    $mailable = new NewsletterPostNotification($data);

    $mailable->assertSeeInHtml(__('newsletter.email.subject'));
    $mailable->assertSeeInHtml(__('newsletter.email.intro'));
    $mailable->assertSeeInHtml(__('newsletter.email.blog_prefix') . ' Test Blog');
    $mailable->assertSeeInHtml(__('newsletter.email.read_more'));
    $mailable->assertSeeInHtml(__('newsletter.email.thanks'));
    $mailable->assertSeeInHtml(__('newsletter.email.manage_subscription'));
    $mailable->assertSeeInHtml(__('newsletter.email.manage_link'));
});
