<?php

use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $this->blogger = User::factory()->create(['role' => UserRole::Blogger->value]);
});

it('provides the administrative blog list only to administrators', function () {
    $adminBlog = Blog::factory()->for($this->admin)->create(['name' => 'Admin blog']);
    $bloggerBlog = Blog::factory()->for($this->blogger)->create(['name' => 'Blogger blog']);

    $this->actingAs($this->admin)
        ->get(route('blogs.index'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/blogger/Blogs')
            ->has('adminBlogs', 2)
            ->where('adminBlogs.0.id', $adminBlog->id)
            ->where('adminBlogs.0.owner_name', $this->admin->name)
            ->where('adminBlogs.1.id', $bloggerBlog->id)
            ->where('adminBlogs.1.owner_name', $this->blogger->name),
        );

    $this->actingAs($this->blogger)
        ->get(route('blogs.index'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/blogger/Blogs')
            ->has('blogs', 1)
            ->missing('adminBlogs'),
        );
});

it('redirects guests and forbids bloggers from updating a Bing token', function () {
    $blog = Blog::factory()->for($this->blogger)->create();

    $this->patch(route('admin.blogs.bing-verification-token.update', $blog), [
        'bing_site_verification_token' => 'unauthorized-token',
    ])->assertRedirect(route('login'));

    $this->actingAs($this->blogger)
        ->patch(route('admin.blogs.bing-verification-token.update', $blog), [
            'bing_site_verification_token' => 'unauthorized-token',
        ])
        ->assertForbidden();

    expect($blog->fresh()->bing_site_verification_token)->toBeNull();
});

it('allows administrators to save and clear a Bing token', function () {
    $blog = Blog::factory()->for($this->blogger)->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.blogs.bing-verification-token.update', $blog), [
            'bing_site_verification_token' => 'bing-token-value',
        ])
        ->assertRedirect();

    expect($blog->fresh()->bing_site_verification_token)->toBe('bing-token-value');

    $this->patch(route('admin.blogs.bing-verification-token.update', $blog), [
        'bing_site_verification_token' => null,
    ])->assertRedirect();

    expect($blog->fresh()->bing_site_verification_token)->toBeNull();
});

it('does not update blog fields outside the validated Bing token', function () {
    $blog = Blog::factory()->for($this->blogger)->create(['name' => 'Original blog']);
    $otherUser = User::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.blogs.bing-verification-token.update', $blog), [
            'bing_site_verification_token' => 'bing-token-value',
            'name' => 'Changed blog',
            'user_id' => $otherUser->id,
        ])
        ->assertRedirect();

    expect($blog->fresh()->name)->toBe('Original blog')
        ->and($blog->fresh()->user_id)->toBe($this->blogger->id)
        ->and($blog->fresh()->bing_site_verification_token)->toBe('bing-token-value');
});

it('rejects a Bing token that is not a string', function () {
    $blog = Blog::factory()->for($this->blogger)->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.blogs.bing-verification-token.update', $blog), [
            'bing_site_verification_token' => ['invalid'],
        ])
        ->assertSessionHasErrors('bing_site_verification_token');

    expect($blog->fresh()->bing_site_verification_token)->toBeNull();
});

it('rejects a Bing token longer than 255 characters', function () {
    $blog = Blog::factory()->for($this->blogger)->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.blogs.bing-verification-token.update', $blog), [
            'bing_site_verification_token' => str_repeat('a', 256),
        ])
        ->assertInvalid([
            'bing_site_verification_token' => '255 characters',
        ]);

    expect($blog->fresh()->bing_site_verification_token)->toBeNull();
});
