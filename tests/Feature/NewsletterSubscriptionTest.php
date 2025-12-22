<?php

use App\Models\Blog;
use App\Models\NewsletterSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('newsletter page can be rendered', function () {
    $blog = Blog::factory()->create(['is_published' => true]);

    $response = $this->get(route('newsletter.index', ['blog_id' => $blog->id]));

    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page
        ->component('public/Newsletter')
        ->has('blogs')
        ->where('selectedBlogId', $blog->id),
    );
});

test('newsletter page pre-selects blog from selectedBlogId', function () {
    $blog = Blog::factory()->create(['is_published' => true]);

    $response = $this->get(route('newsletter.index', ['blog_id' => $blog->id]));

    $response->assertStatus(200);
    // We can't easily test the client-side form state here, but we can verify the prop is passed
    $response->assertInertia(fn($page) => $page
        ->where('selectedBlogId', $blog->id),
    );
});

test('can subscribe to newsletter', function () {
    $blog1 = Blog::factory()->create(['is_published' => true]);
    $blog2 = Blog::factory()->create(['is_published' => true]);

    $response = $this->withCookie('visitor_id', 'test-visitor')
        ->post(route('newsletter.store'), [
            'email' => 'test@example.com',
            'blog_ids' => [$blog1->id, $blog2->id],
            'frequency' => 'weekly',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Zapisano do newslettera pomyślnie!');

    expect(NewsletterSubscription::count())->toBe(2);

    $subscription = NewsletterSubscription::where('blog_id', $blog1->id)->first();
    expect($subscription->email)->toBe('test@example.com')
        ->and($subscription->frequency)->toBe('weekly')
        ->and($subscription->visitor_id)->toBe('test-visitor');
});

test('validates newsletter subscription request', function () {
    $response = $this->post(route('newsletter.store'), [
        'email' => 'invalid-email',
        'blog_ids' => [],
        'frequency' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['email', 'blog_ids', 'frequency']);
});
