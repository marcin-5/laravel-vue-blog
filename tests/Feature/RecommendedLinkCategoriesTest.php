<?php

use App\Models\Blog;
use App\Models\RecommendedLinkCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('lets the blog owner create, update, list, and delete recommended link categories', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();

    actingAs($owner);

    $this->get(route('blogger.recommended-link-categories.index', $blog))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/blogger/RecommendedLinkCategories')
            ->where('blog.id', $blog->id)
            ->where('categories', []));

    $this->post(route('blogger.recommended-link-categories.store', $blog), ['name' => ''])
        ->assertSessionHasErrors('name');

    $this->post(route('blogger.recommended-link-categories.store', $blog), ['name' => 'Research'])
        ->assertRedirect();

    $category = $blog->recommendedLinkCategories()->sole();
    assertDatabaseHas('recommended_link_categories', [
        'id' => $category->id,
        'name' => 'Research',
        'display_order' => 0,
    ]);

    $this->patch(route('blogger.recommended-link-categories.update', [$blog, $category]), ['name' => 'Reading'])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Reading');

    $this->delete(route('blogger.recommended-link-categories.destroy', [$blog, $category]))
        ->assertRedirect();

    assertDatabaseMissing('recommended_link_categories', ['id' => $category->id]);
});

it('stores category order and requires every category in the blog exactly once', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $firstCategory = RecommendedLinkCategory::factory()->for($blog)->create(['display_order' => 0]);
    $secondCategory = RecommendedLinkCategory::factory()->for($blog)->create(['display_order' => 1]);

    actingAs($owner)
        ->put(route('blogger.recommended-link-categories.reorder', $blog), [
            'category_ids' => [$secondCategory->id, $firstCategory->id],
        ])
        ->assertRedirect(route('blogger.recommended-link-categories.index', $blog))
        ->assertSessionHas('success', __('blogger.recommended_link_categories.reordered'));

    assertDatabaseHas('recommended_link_categories', ['id' => $secondCategory->id, 'display_order' => 0]);
    assertDatabaseHas('recommended_link_categories', ['id' => $firstCategory->id, 'display_order' => 1]);

    $this->put(route('blogger.recommended-link-categories.reorder', $blog), [
        'category_ids' => [$secondCategory->id],
    ])->assertSessionHasErrors('category_ids');

    assertDatabaseHas('recommended_link_categories', ['id' => $secondCategory->id, 'display_order' => 0]);
    assertDatabaseHas('recommended_link_categories', ['id' => $firstCategory->id, 'display_order' => 1]);
});

it('rolls back the complete category reorder when an update is canceled', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $firstCategory = RecommendedLinkCategory::factory()->for($blog)->create(['display_order' => 0]);
    $secondCategory = RecommendedLinkCategory::factory()->for($blog)->create(['display_order' => 1]);

    RecommendedLinkCategory::updating(function (RecommendedLinkCategory $category) use ($firstCategory): ?bool {
        return $category->is($firstCategory) ? false : null;
    });

    $this->withoutExceptionHandling();

    expect(fn() => actingAs($owner)
        ->put(route('blogger.recommended-link-categories.reorder', $blog), [
            'category_ids' => [$secondCategory->id, $firstCategory->id],
        ]))->toThrow(RuntimeException::class);

    assertDatabaseHas('recommended_link_categories', ['id' => $firstCategory->id, 'display_order' => 0]);
    assertDatabaseHas('recommended_link_categories', ['id' => $secondCategory->id, 'display_order' => 1]);
});

it('denies access to categories outside the blog owner boundary', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $otherBlog = Blog::factory()->for($otherOwner)->create();
    $category = RecommendedLinkCategory::factory()->for($otherBlog)->create();

    actingAs($owner);

    $this->get(route('blogger.recommended-link-categories.index', $otherBlog))->assertForbidden();
    $this->post(route('blogger.recommended-link-categories.store', $otherBlog), ['name' => 'Denied'])->assertForbidden();
    $this->put(route('blogger.recommended-link-categories.reorder', $otherBlog), [
        'category_ids' => [$category->id],
    ])->assertForbidden();
    $this->patch(route('blogger.recommended-link-categories.update', [$blog, $category]), ['name' => 'Changed'])->assertNotFound();
    $this->delete(route('blogger.recommended-link-categories.destroy', [$blog, $category]))->assertNotFound();
    $this->put(route('blogger.recommended-link-categories.reorder', $blog), [
        'category_ids' => [$category->id],
    ])->assertSessionHasErrors('category_ids.0');

    expect($category->fresh()->name)->not->toBe('Changed');
});

it('redirects guests before they can manage recommended link categories', function () {
    $blog = Blog::factory()->for(User::factory())->create();

    $this->get(route('blogger.recommended-link-categories.index', $blog))
        ->assertRedirect(route('login'));
});
