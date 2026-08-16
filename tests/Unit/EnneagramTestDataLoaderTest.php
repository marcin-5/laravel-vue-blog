<?php

use App\Services\Enneagram\EnneagramTestDataLoader;
use Illuminate\Filesystem\Filesystem;

test('loads and normalizes the localized enneagram data', function () {
    config(['enneagram.data_cache.enabled' => false]);

    $data = app(EnneagramTestDataLoader::class)->load('pl');

    expect($data['questions'])->not->toBeEmpty()
        ->and($data['config']['stages']['stage1']['part1']['maxQuestions'])->toBeInt()
        ->and($data['locale'])->toBe('pl');
});

test(
    /**
     * @throws Throwable
     */ 'rejects malformed json from either source file', function () {
        config(['enneagram.data_cache.enabled' => false]);
        $filesystem = Mockery::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')->twice()->andReturnTrue();
        $filesystem->shouldReceive('get')->once()->andReturn('{ invalid json');

        $loader = new EnneagramTestDataLoader($filesystem, '/questions', '/config');

        expect(fn() => $loader->load('en'))
            ->toThrow(RuntimeException::class, 'Unable to decode enneagram questions data.');
    });

test(
    /**
     * @throws Throwable
     */ 'rejects data with an invalid question shape', function () {
        config(['enneagram.data_cache.enabled' => false]);
        $filesystem = Mockery::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')->twice()->andReturnTrue();
        $filesystem->shouldReceive('get')->atLeast()->once()->andReturn(json_encode([['id' => 'di-01']]));

        $loader = new EnneagramTestDataLoader($filesystem, '/questions', '/config');

        expect(fn() => $loader->load('en'))
            ->toThrow(RuntimeException::class, 'Each enneagram question must contain');
    });
