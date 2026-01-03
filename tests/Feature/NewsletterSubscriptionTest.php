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
            'subscriptions' => [
                ['blog_id' => $blog1->id, 'frequency' => 'weekly', 'send_time' => '12:00', 'send_day' => 1],
                ['blog_id' => $blog2->id, 'frequency' => 'daily'],
            ],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Zapisano do newslettera pomyślnie!');

    expect(NewsletterSubscription::count())->toBe(2);

    $subscription = NewsletterSubscription::where('blog_id', $blog1->id)->first();
    expect($subscription->email)->toBe('test@example.com')
        ->and($subscription->frequency)->toBe('weekly')
        ->and($subscription->send_time)->toBe('12:00')
        ->and($subscription->send_day)->toBe(1)
        ->and($subscription->visitor_id)->toBe('test-visitor');
});

test('validates newsletter subscription request', function () {
    $response = $this->post(route('newsletter.store'), [
        'email' => 'invalid-email',
        'subscriptions' => [],
    ]);

    $response->assertSessionHasErrors(['email', 'subscriptions']);
});

test('newsletter management page requires valid signature', function () {
    $response = $this->get(route('newsletter.manage', ['email' => 'test@example.com']));

    $response->assertStatus(403);
});

test('newsletter management page renders with valid signature', function () {
    $blog = Blog::factory()->create(['is_published' => true]);
    NewsletterSubscription::factory()->create([
        'email' => 'test@example.com',
        'blog_id' => $blog->id,
        'frequency' => 'weekly',
        'send_time' => '19:19',
        'send_day' => 7,
    ]);

    $url = URL::signedRoute('newsletter.manage', ['email' => 'test@example.com']);

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page
        ->component('public/Newsletter')
        ->where('mode', 'manage')
        ->where('email', 'test@example.com')
        ->has('currentSubscriptions', 1)
        ->where('currentSubscriptions.0.blog_id', $blog->id)
        ->where('currentSubscriptions.0.frequency', 'weekly')
        ->where('currentSubscriptions.0.send_time', '19:19')
        ->where('currentSubscriptions.0.send_day', 7)
        ->has('updateUrl')
        ->has('unsubscribeUrl'),
    );
});

test('can update newsletter subscriptions via management page', function () {
    $blog1 = Blog::factory()->create(['is_published' => true]);
    $blog2 = Blog::factory()->create(['is_published' => true]);
    NewsletterSubscription::factory()->create([
        'email' => 'test@example.com',
        'blog_id' => $blog1->id,
        'frequency' => 'daily',
    ]);

    $url = URL::signedRoute('newsletter.manage', ['email' => 'test@example.com']);
    $manageResponse = $this->get($url);
    $updateUrl = $manageResponse->inertiaPage()['props']['updateUrl'];

    $response = $this->post($updateUrl, [
        'email' => 'test@example.com',
        'subscriptions' => [
            ['blog_id' => $blog2->id, 'frequency' => 'weekly', 'send_time' => '10:00', 'send_day' => 2],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Ustawienia newslettera zostały zaktualizowane.');

    expect(NewsletterSubscription::where('email', 'test@example.com')->count())->toBe(1);
    $subscription = NewsletterSubscription::where('email', 'test@example.com')->first();
    expect($subscription->blog_id)->toBe($blog2->id)
        ->and($subscription->frequency)->toBe('weekly')
        ->and($subscription->send_time)->toBe('10:00')
        ->and($subscription->send_day)->toBe(2);
});

test('can unsubscribe from all via management page', function () {
    $blog = Blog::factory()->create(['is_published' => true]);
    NewsletterSubscription::factory()->create([
        'email' => 'test@example.com',
        'blog_id' => $blog->id,
    ]);

    $url = URL::signedRoute('newsletter.manage', ['email' => 'test@example.com']);
    $manageResponse = $this->get($url);
    $unsubscribeUrl = $manageResponse->inertiaPage()['props']['unsubscribeUrl'];

    $response = $this->post($unsubscribeUrl, ['email' => 'test@example.com']);

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('message', 'Zostałeś wypisany z newslettera.');

    expect(NewsletterSubscription::where('email', 'test@example.com')->count())->toBe(0);
});
