<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders markdown content and footer to html on group landing page', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create([
        'user_id' => $user->id,
        'slug' => 'test-group',
        'content' => '# Hello World',
        'footer' => '*Regards*, Team',
    ]);

    $this->actingAs($user)
        ->get(route('group.landing', $group->slug))
        ->assertOk()
        ->assertInertia(fn($page) => $page
            ->component('app/group/Landing')
            ->where('group.content', "<h1>Hello World</h1>")
            ->where('group.footer', "<p><em>Regards</em>, Team</p>"),
        );
});
