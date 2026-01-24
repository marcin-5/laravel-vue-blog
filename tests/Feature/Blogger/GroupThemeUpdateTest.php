<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('updates group theme values', function () {
    $user = User::factory()->create(['role' => 'blogger']);
    $group = Group::factory()->create([
        'user_id' => $user->id,
        'theme' => null,
    ]);

    $payload = [
        'name' => $group->name,
        'theme' => [
            'light' => ['background' => '#fefefe', 'primary' => '#012345'],
            'dark' => ['background' => '#b0ab0a', 'primary' => '#e5e5e5'],
        ],
    ];

    $response = actingAs($user)
        ->put(route('blogger.groups.content.update', $group), $payload);

    $response->assertRedirect();

    $group->refresh();

    expect($group->theme)->toMatchArray($payload['theme']);
});
