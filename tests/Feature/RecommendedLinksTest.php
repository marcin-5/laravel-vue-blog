<?php

use App\Models\Blog;
use App\Models\RecommendedLink;
use App\Models\RecommendedLinkCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;

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
    $this->putJson(route('blogger.recommended-links.reorder', $otherBlog), [
        'category_id' => null,
        'link_ids' => [$link->id],
    ])->assertForbidden();
    $this->patch(route('blogger.recommended-links.update', [$blog, $link]), ['url' => 'https://changed.example'])->assertNotFound();
    $this->delete(route('blogger.recommended-links.destroy', [$blog, $link]))->assertNotFound();

    expect($link->fresh()->url)->toBe($link->url);
});

it('redirects guests to sign in before managing recommended links', function () {
    $blog = Blog::factory()->for(User::factory())->create();

    $this->get(route('blogger.recommended-links.index', $blog))->assertRedirect(route('login'));
});

it('orders recommended links by display order and then by id', function () {
    $blog = Blog::factory()->for(User::factory())->create();
    $laterLink = RecommendedLink::factory()->for($blog)->create(['display_order' => 2]);
    $firstLink = RecommendedLink::factory()->for($blog)->create(['display_order' => 1]);
    $secondLink = RecommendedLink::factory()->for($blog)->create(['display_order' => 1]);

    expect($blog->recommendedLinks()->pluck('id')->all())->toBe([
        $firstLink->id,
        $secondLink->id,
        $laterLink->id,
    ]);
});

it('keeps recommended links when their category is deleted', function () {
    $blog = Blog::factory()->for(User::factory())->create();
    $category = RecommendedLinkCategory::factory()->for($blog)->create();
    $link = RecommendedLink::factory()->for($blog)->create(['category_id' => $category->id]);

    $category->delete();

    assertDatabaseHas('recommended_links', [
        'id' => $link->id,
        'category_id' => null,
    ]);
});

it('allows assigning, changing, and clearing a recommended link category', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $firstCategory = RecommendedLinkCategory::factory()->for($blog)->create();
    $secondCategory = RecommendedLinkCategory::factory()->for($blog)->create();

    actingAs($owner)
        ->post(route('blogger.recommended-links.store', $blog), [
            'url' => 'https://example.com/resources',
            'category_id' => $firstCategory->id,
        ])
        ->assertRedirect();

    $link = $blog->recommendedLinks()->sole();
    expect($link->category_id)->toBe($firstCategory->id);

    $this->patch(route('blogger.recommended-links.update', [$blog, $link]), [
        'url' => $link->url,
        'category_id' => $secondCategory->id,
    ])->assertRedirect();

    expect($link->fresh()->category_id)->toBe($secondCategory->id);

    $this->patch(route('blogger.recommended-links.update', [$blog, $link]), [
        'url' => $link->url,
        'category_id' => null,
    ])->assertRedirect();

    expect($link->fresh()->category_id)->toBeNull();
});

it('rejects categories belonging to another blog when creating or updating links', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $otherBlog = Blog::factory()->for(User::factory())->create();
    $foreignCategory = RecommendedLinkCategory::factory()->for($otherBlog)->create();
    $link = RecommendedLink::factory()->for($blog)->create();

    actingAs($owner)
        ->post(route('blogger.recommended-links.store', $blog), [
            'url' => 'https://example.com/resources',
            'category_id' => $foreignCategory->id,
        ])
        ->assertSessionHasErrors('category_id');

    $this->patch(route('blogger.recommended-links.update', [$blog, $link]), [
        'url' => $link->url,
        'category_id' => $foreignCategory->id,
    ])->assertSessionHasErrors('category_id');

    expect($blog->recommendedLinks()->count())->toBe(1)
        ->and($link->fresh()->category_id)->toBeNull();
});

it('reorders links only within their category or uncategorized section', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $category = RecommendedLinkCategory::factory()->for($blog)->create();
    $uncategorizedFirst = RecommendedLink::factory()->for($blog)->create(['display_order' => 0]);
    $uncategorizedSecond = RecommendedLink::factory()->for($blog)->create(['display_order' => 1]);
    $categorizedFirst = RecommendedLink::factory()->for($blog)->create([
        'category_id' => $category->id,
        'display_order' => 0,
    ]);
    $categorizedSecond = RecommendedLink::factory()->for($blog)->create([
        'category_id' => $category->id,
        'display_order' => 1,
    ]);

    actingAs($owner)
        ->put(route('blogger.recommended-links.reorder', $blog), [
            'category_id' => null,
            'link_ids' => [$uncategorizedSecond->id, $uncategorizedFirst->id],
        ])
        ->assertRedirect(route('blogger.recommended-links.index', $blog))
        ->assertSessionHas('success', __('blogger.recommended_links.reordered'));

    $this->put(route('blogger.recommended-links.reorder', $blog), [
        'category_id' => $category->id,
        'link_ids' => [$categorizedSecond->id, $categorizedFirst->id],
    ])->assertRedirect(route('blogger.recommended-links.index', $blog));

    assertDatabaseHas('recommended_links', ['id' => $uncategorizedSecond->id, 'display_order' => 0]);
    assertDatabaseHas('recommended_links', ['id' => $uncategorizedFirst->id, 'display_order' => 1]);
    assertDatabaseHas('recommended_links', ['id' => $categorizedSecond->id, 'display_order' => 0]);
    assertDatabaseHas('recommended_links', ['id' => $categorizedFirst->id, 'display_order' => 1]);

    $this->put(route('blogger.recommended-links.reorder', $blog), [
        'category_id' => $category->id,
        'link_ids' => [$categorizedFirst->id, $uncategorizedFirst->id],
    ])->assertSessionHasErrors('link_ids');
});

it('rolls back every link order update when one link fails to save', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $firstLink = RecommendedLink::factory()->for($blog)->create(['display_order' => 0]);
    $secondLink = RecommendedLink::factory()->for($blog)->create(['display_order' => 1]);

    RecommendedLink::updating(function (RecommendedLink $link) use ($firstLink): ?bool {
        return $link->is($firstLink) ? false : null;
    });

    $this->withoutExceptionHandling();

    expect(fn() => actingAs($owner)
        ->put(route('blogger.recommended-links.reorder', $blog), [
            'category_id' => null,
            'link_ids' => [$secondLink->id, $firstLink->id],
        ]))->toThrow(RuntimeException::class);

    assertDatabaseHas('recommended_links', ['id' => $firstLink->id, 'display_order' => 0]);
    assertDatabaseHas('recommended_links', ['id' => $secondLink->id, 'display_order' => 1]);
});
