<?php

use App\Models\Blog;
use App\Models\Tag;
use App\Models\TagLandingPage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('renders tag management with blog tags and landing page content', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $tag = Tag::factory()->for($blog)->create(['name' => 'Laravel', 'slug' => 'laravel']);
    TagLandingPage::factory()->for($tag)->create(['content' => '# Laravel']);

    actingAs($owner)
        ->get(route('blogger.tags.index', $blog))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/blogger/Tags')
            ->where('blog.id', $blog->id)
            ->has('tags', 1)
            ->where('tags.0.id', $tag->id)
            ->where('tags.0.landing_page_content', '# Laravel'));
});

it('creates updates and deletes tags through Inertia redirects', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();

    actingAs($owner);

    $this->post(route('blogger.tags.store', $blog), [
        'name' => 'Laravel',
    ])->assertRedirect(route('blogger.tags.index', $blog))
        ->assertSessionHas('success', __('blogger.tags.created'));

    $tag = Tag::query()->where('blog_id', $blog->id)->where('slug', 'laravel')->firstOrFail();

    $this->patch(route('blogger.tags.update', [$blog, $tag]), [
        'name' => 'PHP',
    ])->assertRedirect(route('blogger.tags.index', $blog))
        ->assertSessionHas('success', __('blogger.tags.updated'));

    expect($tag->fresh()->name)->toBe('PHP')
        ->and($tag->fresh()->slug)->toBe('php');

    $this->post(route('blogger.tags.store', $blog), [
        'name' => 'PHP',
    ])->assertSessionHasErrors('slug');

    $this->delete(route('blogger.tags.destroy', [$blog, $tag->fresh()]))
        ->assertRedirect(route('blogger.tags.index', $blog))
        ->assertSessionHas('success', __('blogger.tags.deleted'));

    expect(Tag::whereKey($tag->id)->exists())->toBeFalse();
});

it('saves markdown landing page content through an Inertia redirect', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $tag = Tag::factory()->for($blog)->create();

    actingAs($owner)
        ->put(route('blogger.tags.landing-page.update', [$blog, $tag]), [
            'content' => '# Tag content',
        ])->assertRedirect(route('blogger.tags.index', $blog))
        ->assertSessionHas('success', __('blogger.tags.landing_page_saved'));

    expect($tag->fresh()->landingPage->content)->toBe('# Tag content');
});

it('returns 404 when a blog owner submits another blogs tag', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $otherBlog = Blog::factory()->for(User::factory()->create())->create();
    $tag = Tag::factory()->for($otherBlog)->create();

    actingAs($owner);

    $this->patch(route('blogger.tags.update', [$blog, $tag]), [
        'name' => 'Should not be saved',
    ])->assertNotFound();

    $this->put(route('blogger.tags.landing-page.update', [$blog, $tag]), [
        'content' => 'Should not be saved',
    ])->assertNotFound();

    expect(TagLandingPage::query()->exists())->toBeFalse();
});

it('forbids users without blog access from managing tags', function () {
    $blog = Blog::factory()->for(User::factory())->create();
    $tag = Tag::factory()->for($blog)->create();
    $otherUser = User::factory()->create();

    actingAs($otherUser);

    $this->post(route('blogger.tags.store', $blog), ['name' => 'Unauthorized'])->assertForbidden();
    $this->delete(route('blogger.tags.destroy', [$blog, $tag]))->assertForbidden();

    expect(Tag::query()->where('blog_id', $blog->id)->count())->toBe(1)
        ->and($tag->fresh()->name)->not->toBe('Unauthorized');
});

it('keeps the post tag selector read-only endpoint as JSON', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $tag = Tag::factory()->for($blog)->create(['name' => 'Laravel', 'slug' => 'laravel']);

    actingAs($owner)
        ->getJson(route('blogger.tags.options', $blog))
        ->assertOk()
        ->assertExactJson([['id' => $tag->id, 'name' => 'Laravel', 'slug' => 'laravel']]);
});
