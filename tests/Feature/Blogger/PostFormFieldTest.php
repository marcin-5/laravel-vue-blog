<?php

use App\Models\Blog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('renders post form fields correctly in the blogs page', function () {
    $user = User::factory()->create(['role' => User::ROLE_BLOGGER]);
    Blog::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->get(route('blogs.index'))
        ->assertStatus(200)
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/blogger/Blogs')
            ->has('blogs'),
        );
});
