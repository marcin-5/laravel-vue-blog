<?php

use App\Models\Post;

it('generates slug from title when slug is not provided', function () {
    $post = new Post([
        'title' => 'Kolejny post',
    ]);

    // Wymusza wywołanie mutatora
    $post->slug = null;

    expect($post->slug)->toBe('kolejny-post');
});

it('normalizes provided slug value', function () {
    $post = new Post([
        'title' => 'Ignorowany tytuł',
    ]);

    $post->slug = 'Kolejny post';

    expect($post->slug)->toBe('kolejny-post');
});

it('handles titles with polish characters', function () {
    $post = new Post([
        'title' => 'Zażółć gęślą jaźń',
    ]);

    $post->slug = null;

    expect($post->slug)->toBe('zazolc-gesla-jazn');
});
