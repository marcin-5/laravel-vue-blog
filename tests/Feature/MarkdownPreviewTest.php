<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('can preview markdown content', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson(route('markdown.preview'), [
            'content' => '# Hello World',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['html']);
});

it('requires content for markdown preview', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson(route('markdown.preview'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);
});

it('requires authentication for markdown preview', function () {
    $this->postJson(route('markdown.preview'), [
        'content' => '# Hello World',
    ])->assertUnauthorized();
});
