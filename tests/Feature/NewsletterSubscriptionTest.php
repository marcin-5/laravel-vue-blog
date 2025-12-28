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
    ]);

    $url = URL::signedRoute('newsletter.manage', ['email' => 'test@example.com']);

    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page
        ->component('public/NewsletterManage')
        ->where('email', 'test@example.com')
        ->where('frequency', 'weekly')
        ->has('currentSubscriptions', 1)
        ->where('currentSubscriptions.0', $blog->id)
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
        'blog_ids' => [$blog2->id],
        'frequency' => 'weekly',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Ustawienia newslettera zostały zaktualizowane.');

    expect(NewsletterSubscription::where('email', 'test@example.com')->count())->toBe(1);
    $subscription = NewsletterSubscription::where('email', 'test@example.com')->first();
    expect($subscription->blog_id)->toBe($blog2->id)
        ->and($subscription->frequency)->toBe('weekly');
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
