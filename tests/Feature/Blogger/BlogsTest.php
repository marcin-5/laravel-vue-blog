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
            'theme' => $themeData,
        ])
        ->assertRedirect();

    $blog->refresh();
    expect($blog->theme)->toBe($themeData);
});

it('can set theme to null or empty', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'theme' => ['light' => ['--background' => '#ff0000']]
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
