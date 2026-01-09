<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('allows authenticated users to preview markdown', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->postJson(route('markdown.preview'), [
            'content' => '# Hello World',
        ]);

    $response->assertOk()
        ->assertJsonStructure(['html'])
        ->assertJson(['html' => "<h1>Hello World</h1>"]);
});

it('validates markdown content', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->postJson(route('markdown.preview'), [
            'content' => '',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

it('requires authentication to preview markdown', function () {
    $response = $this->postJson(route('markdown.preview'), [
        'content' => '# Hello World',
    ]);

    $response->assertStatus(401);
});
