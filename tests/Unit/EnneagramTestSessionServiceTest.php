<?php

use App\Services\Enneagram\EnneagramTestSessionService;

test('persists, advances and resets an enneagram test in the session', function () {
    config(['enneagram.data_cache.enabled' => false]);

    $service = app(EnneagramTestSessionService::class);
    $started = $service->start(false, 'en');
    $testId = $started['testId'];

    expect($testId)->toHaveLength(40)
        ->and(session(config('enneagram.session.key')))->toHaveKey($testId);

    $advanced = $service->apply($testId, 'answer', [$started['state']['options'][0]]);

    expect($advanced['testId'])->toBe($testId)
        ->and($advanced['state']['allowed_actions']['back'])->toBeTrue();

    $service->reset($testId);

    expect(session(config('enneagram.session.key')))->not->toHaveKey($testId);
});
