<?php

it('contains required public blog posts list translation keys (en and pl)', function (string $locale) {
    $path = base_path("resources/lang/{$locale}/public.json");
    expect(file_exists($path))->toBeTrue();

    $translations = json_decode(file_get_contents($path), true);
    expect($translations)->toBeArray();

    $requiredKeys = [
        'blog.posts_list.aria',
        'blog.posts_list.title',
        'blog.posts_list.empty',
        'blog.posts_list.show_excerpts',
        'blog.posts_list.view_excerpt',
    ];

    foreach ($requiredKeys as $key) {
        expect(data_get($translations, $key))->not->toBeNull();
    }
})->with([
    'en',
    'pl',
]);
