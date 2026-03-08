<?php

use App\Services\TranslationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

it('merges translations correctly without cache', function () {
    Config::set('translations.page_groups.home', ['public', 'common']);
    Config::set('translations.include_root_json', true);
    Config::set('translations.cache_ttl', 0);

    File::shouldReceive('exists')
        ->with(resource_path('lang/en.json'))
        ->andReturn(true);

    File::shouldReceive('get')
        ->with(resource_path('lang/en.json'))
        ->andReturn(json_encode(['root' => 'value']));

    File::shouldReceive('exists')
        ->with(resource_path('lang/en/public.json'))
        ->andReturn(true);

    File::shouldReceive('get')
        ->with(resource_path('lang/en/public.json'))
        ->andReturn(json_encode(['public' => 'value']));

    File::shouldReceive('exists')
        ->with(resource_path('lang/en/common.json'))
        ->andReturn(false);

    File::shouldReceive('exists')
        ->with(resource_path('lang/en/common.php'))
        ->andReturn(true);

    File::shouldReceive('getRequire')
        ->with(resource_path('lang/en/common.php'))
        ->andReturn(['common' => 'value']);

    $service = new TranslationService;
    $translations = $service->getPageTranslations('home');

    expect($translations)->toEqual([
        'root' => 'value',
        'public' => 'value',
        'common' => 'value',
    ]);
});

it('caches translations when ttl is set', function () {
    Config::set('translations.page_groups.home', ['public']);
    Config::set('translations.include_root_json', false);
    Config::set('translations.cache_ttl', 60);

    File::shouldReceive('exists')
        ->with(resource_path('lang/en/public.json'))
        ->andReturn(true);

    File::shouldReceive('get')
        ->with(resource_path('lang/en/public.json'))
        ->andReturn(json_encode(['public' => 'cached']));

    $service = new TranslationService;

    // First call, should load and cache
    $translations1 = $service->getPageTranslations('home');
    expect($translations1)->toEqual(['public' => 'cached']);

    // Second call should return from cache (we check it by mocking the result of remember)
    // Actually, Cache::remember works automatically in this test environment.
    // Let's verify it actually hits the cache.

    // We can use Cache facade to assert.
    expect(Cache::has('page_translations:en:home'))->toBeTrue();
});
