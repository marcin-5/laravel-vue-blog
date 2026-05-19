<?php

use App\Models\Blog;
use App\Models\Tag;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

it('allows owner to CRUD tags for a blog with unique slug per blog', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();

    actingAs($owner);

    // Create
    $create = postJson(route('blogger.tags.store', $blog), [
        'name' => 'Laravel',
    ])->assertCreated();

    $tagId = $create->json('id');
    $tagSlug = $create->json('slug');
    expect(Tag::whereKey($tagId)->where('blog_id', $blog->id)->exists())->toBeTrue();

    // Index
    getJson(route('blogger.tags.index', $blog))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Laravel']);

    // Update
    $updated = patchJson(route('blogger.tags.update', [$blog, $tagSlug]), [
        'name' => 'PHP',
    ])->assertOk()->assertJsonFragment(['name' => 'PHP']);
    $tagSlug = $updated->json('slug');

    // Uniqueness per blog
    postJson(route('blogger.tags.store', $blog), [
        'name' => 'PHP', // same slug -> should be 422 unique
    ])->assertUnprocessable();

    // Delete
    deleteJson(route('blogger.tags.destroy', [$blog, $tagSlug]))->assertOk();
    expect(Tag::whereKey($tagId)->exists())->toBeFalse();
});
