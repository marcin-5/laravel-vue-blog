<?php

use App\Models\Blog;
use App\Models\Tag;
use App\Models\TagLandingPage;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;
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

it('allows a blog owner to read and save markdown landing content for a tag', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $tag = Tag::factory()->for($blog)->create();

    actingAs($owner);

    getJson(route('blogger.tags.landing-page.show', [$blog, $tag]))
        ->assertOk()
        ->assertExactJson(['content' => '']);

    putJson(route('blogger.tags.landing-page.update', [$blog, $tag]), [
        'content' => '# Tag content',
    ])->assertOk()->assertExactJson(['content' => '# Tag content']);

    expect($tag->fresh()->landingPage->content)->toBe('# Tag content');
});

it('returns 404 when a blog owner requests landing content for another blogs tag', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $otherBlog = Blog::factory()->for(User::factory()->create())->create();
    $tag = Tag::factory()->for($otherBlog)->create();

    actingAs($owner);

    getJson(route('blogger.tags.landing-page.show', [$blog, $tag]))->assertNotFound();
    putJson(route('blogger.tags.landing-page.update', [$blog, $tag]), [
        'content' => 'Should not be saved',
    ])->assertNotFound();

    expect(TagLandingPage::query()->exists())->toBeFalse();
});
