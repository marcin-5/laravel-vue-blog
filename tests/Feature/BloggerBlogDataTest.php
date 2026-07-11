<?php

use App\Models\Blog;
use App\Models\User;

test('blogger dashboard includes about field in blogs data', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'about' => 'This is the about content for the dashboard test.',
    ]);

    $response = $this->actingAs($user)
        ->get(route('blogs.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page
        ->component('app/blogger/Blogs')
        ->has('blogs.0', fn($page) => $page
            ->where('about', 'This is the about content for the dashboard test.')
            ->etc()
        )
    );
});
