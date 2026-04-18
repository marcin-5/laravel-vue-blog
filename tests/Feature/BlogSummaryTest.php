<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;

it('includes summary in posts for index', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id]);
    Post::factory()->create([
        'blog_id' => $blog->id,
        'summary' => 'This is a test summary',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('blogs.index'));

    $response->assertStatus(200);

    $response->assertInertia(fn($page) => $page
        ->has('blogs')
        ->where('blogs.0.posts.0.summary', 'This is a test summary'),
    );
});
