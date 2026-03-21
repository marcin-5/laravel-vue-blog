<?php

use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('saves landing_content when creating a blog', function () {
    $user = User::factory()->create([
        'role' => UserRole::Admin->value,
    ]);

    $blogData = [
        'name' => 'New Blog with Landing Content',
        'seo_title' => 'SEO Title',
        'landing_content' => 'This is the landing page content.',
        'description' => 'Blog description',
        'is_published' => true,
    ];

    actingAs($user)
        ->post(route('blogs.store'), $blogData)
        ->assertRedirect(route('blogs.index'));

    $blog = Blog::where('name', 'New Blog with Landing Content')->first();

    expect($blog)->not
        ->toBeNull()
        ->and($blog->landingPage)->not
        ->toBeNull()
        ->and($blog->landingPage->content)->toBe('This is the landing page content.');
});
