<?php

use App\Models\PageView;
use App\Models\UserAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('links created PageView to a normalized UserAgent and reuses existing record', function () {
    // Given the same raw UA used twice
    $raw = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    $first = PageView::factory()->create(['user_agent' => $raw]);
    $second = PageView::factory()->create(['user_agent' => $raw]);

    // Then both page views should reference the same UserAgent
    expect($first->user_agent_id)->not()->toBeNull()
        ->and($second->user_agent_id)->toBe($first->user_agent_id);

    $ua = UserAgent::query()->find($first->user_agent_id);
    expect($ua)->not()->toBeNull()
        ->and($ua->name)->toBeString()->not->toBe('');
});

it('maps bot user agents to a capitalized fragment label', function () {
    // Common bot fragment example; ensure config contains this or adjust accordingly
    $raw = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    $pv = PageView::factory()->create(['user_agent' => $raw]);

    $ua = $pv->userAgent()->first();
    // Label should be a capitalized fragment like "Googlebot"
    expect($ua)->not()->toBeNull()
        ->and($ua->name)->toContain('Googlebot');
});

it('uses Unknown for empty or null user agents', function () {
    $pv1 = PageView::factory()->create(['user_agent' => '']);
    $pv2 = PageView::factory()->create(['user_agent' => null]);

    $ua1 = $pv1->userAgent()->first();
    $ua2 = $pv2->userAgent()->first();

    expect($ua1->name)->toBe('Unknown')
        ->and($ua2->name)->toBe('Unknown');
});
