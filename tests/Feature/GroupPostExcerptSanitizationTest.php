<?php

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sanitizes group post excerpts before they reach the page', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $user->id, 'slug' => 'excerpt-group']);
    Post::factory()->create([
        'group_id' => $group->id,
        'visibility' => Post::VIS_RESTRICTED,
        'is_published' => true,
        'published_at' => now()->subDay(),
        'title' => 'Unsafe Excerpt Post',
        'excerpt' => 'Safe **text** <script>alert(1)</script>' . '<' . 'img src=x onerror=alert(1)' . '>',
    ]);

    $this->actingAs($user)
        ->get(route('group.landing', $group->slug))
        ->assertSuccessful()
        ->assertInertia(fn($page) => $page
            ->component('app/group/Landing')
            ->where('posts.0.excerpt', fn(string $excerpt) => str_contains($excerpt, '<strong>text</strong>')
                && !str_contains($excerpt, '<script')
                && !str_contains($excerpt, 'onerror')),
        );
});
