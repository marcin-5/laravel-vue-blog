<?php

use App\Services\BotDetector;
use Illuminate\Http\Request;

it('detects bots from user agent fragments', function () {
    $botDetector = new BotDetector();

    // Normal user
    $request = Request::create('/', 'GET');
    $request->headers->set(
        'User-Agent',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    );
    expect($botDetector->isBot($request))->toBeFalse();

    // Googlebot
    $request->headers->set('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
    expect($botDetector->isBot($request))->toBeTrue();

    // Meta External Agent
    $request->headers->set(
        'User-Agent',
        'meta-externalagent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)',
    );
    expect($botDetector->isBot($request))->toBeTrue();
});
