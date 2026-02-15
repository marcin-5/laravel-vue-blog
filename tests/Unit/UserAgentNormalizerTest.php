<?php

use App\Services\UserAgentNormalizer;

test('it returns unknown for empty user agent', function () {
    $normalizer = new UserAgentNormalizer;

    expect($normalizer->normalize(''))->toBe('Unknown')
        ->and($normalizer->normalize(null))->toBe('Unknown');
});

test('it normalizes bot user agents to their family', function () {
    $normalizer = new UserAgentNormalizer;
    $botUserAgent = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    expect($normalizer->normalize($botUserAgent))->toBe('Googlebot');
});

test('it formats non-bot user agents with browser, os, and device', function () {
    $normalizer = new UserAgentNormalizer;
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $normalized = $normalizer->normalize($userAgent);

    expect($normalized)
        ->toContain(' on ')
        ->toContain('(')
        ->toContain(')');
});
