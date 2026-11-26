<?php

use App\Models\Blog;
use App\Models\RecommendedLink;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('lets the blog owner create, update, and delete recommended links', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();

    actingAs($owner);

    $this->get(route('blogger.recommended-links.index', $blog))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/blogger/RecommendedLinks')
            ->where('blog.id', $blog->id)
            ->where('recommendedLinks', []));

    $this->post(route('blogger.recommended-links.store', $blog), [
        'url' => 'https://example.com/resources',
        'name' => 'Example',
        'description' => 'Useful resources',
    ])->assertRedirect();

    $link = RecommendedLink::query()->where('blog_id', $blog->id)->sole();
    assertDatabaseHas('recommended_links', [
        'id' => $link->id,
        'url' => 'https://example.com/resources',
        'name' => 'Example',
        'description' => 'Useful resources',
    ]);

    $this->patch(route('blogger.recommended-links.update', [$blog, $link]), [
        'url' => 'https://example.org/',
        'name' => null,
        'description' => null,
    ])->assertRedirect();

    expect($link->fresh()->url)->toBe('https://example.org/')
        ->and($link->fresh()->name)->toBeNull()
        ->and($link->fresh()->description)->toBeNull();

    $this->delete(route('blogger.recommended-links.destroy', [$blog, $link]))->assertRedirect();

    assertDatabaseMissing('recommended_links', ['id' => $link->id]);
});

it('rejects invalid recommended link data without saving it', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();

    actingAs($owner)
        ->post(route('blogger.recommended-links.store', $blog), [
            'url' => 'not-a-url',
            'name' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors(['url', 'name']);

    expect($blog->recommendedLinks()->exists())->toBeFalse();
});

it('rejects invalid data when updating a recommended link', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $link = RecommendedLink::factory()->for($blog)->create([
        'url' => 'https://example.com/original',
    ]);

    actingAs($owner)
        ->patch(route('blogger.recommended-links.update', [$blog, $link]), ['url' => 'not-a-url'])
        ->assertSessionHasErrors(['url']);

    expect($link->fresh()->url)->toBe('https://example.com/original');
});

it('denies access to recommended links managed by another blog owner', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $otherBlog = Blog::factory()->for($otherOwner)->create();
    $link = RecommendedLink::factory()->for($otherBlog)->create();

    actingAs($owner);

    $this->get(route('blogger.recommended-links.index', $otherBlog))->assertForbidden();
    $this->post(route('blogger.recommended-links.store', $otherBlog), ['url' => 'https://example.com'])->assertForbidden();
    $this->patch(route('blogger.recommended-links.update', [$blog, $link]), ['url' => 'https://changed.example'])->assertNotFound();
    $this->delete(route('blogger.recommended-links.destroy', [$blog, $link]))->assertNotFound();

    expect($link->fresh()->url)->toBe($link->url);
});

it('redirects guests to sign in before managing recommended links', function () {
    $blog = Blog::factory()->for(User::factory())->create();

    $this->get(route('blogger.recommended-links.index', $blog))->assertRedirect(route('login'));
});
