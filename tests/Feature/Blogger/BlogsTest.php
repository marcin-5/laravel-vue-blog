<?php

use App\Models\Blog;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('updates blog theme colors', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id]);

    $themeData = [
        'light' => [
            '--background' => '#ffffff',
            '--foreground' => '#000000',
        ],
        'dark' => [
            '--background' => '#111111',
            '--foreground' => '#eeeeee',
        ],
    ];

    actingAs($user)
        ->patch(route('blogs.update', $blog), [
            'name' => 'Updated Name',
            'seo_title' => 'Updated SEO Title',
            'theme' => $themeData,
        ])
        ->assertRedirect();

    $blog->refresh();
    expect($blog->name)
        ->toBe('Updated Name')
        ->and($blog->seo_title)->toBe('Updated SEO Title')
        ->and($blog->theme)->toBe($themeData);
});

it('allows bloggers to enable multiple tag filtering', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id]);

    expect($blog->allow_multiple_tag_filtering)->toBeFalse();

    actingAs($user)
        ->patch(route('blogs.update', $blog), [
            'allow_multiple_tag_filtering' => true,
        ])
        ->assertRedirect();

    expect($blog->refresh()->allow_multiple_tag_filtering)->toBeTrue();
});

it('rejects invalid values for multiple tag filtering', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->patch(route('blogs.update', $blog), [
            'allow_multiple_tag_filtering' => 'enabled',
        ])
        ->assertSessionHasErrors('allow_multiple_tag_filtering');

    expect($blog->refresh()->allow_multiple_tag_filtering)->toBeFalse();
});

it('can set theme to null or empty', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'theme' => ['light' => ['--background' => '#ff0000']],
    ]);

    actingAs($user)
        ->patch(route('blogs.update', $blog), [
            'name' => 'Updated Name',
            'theme' => null,
        ])
        ->assertRedirect();

    $blog->refresh();
    expect($blog->theme)->toBeNull();
});
