<?php

use App\Http\Middleware\UpdateVisitorOnLogin;
use App\Models\PageView;
use App\Models\User;
use App\Services\FingerprintGenerator;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

// Helper to run the UpdateVisitorOnLogin middleware directly on a Request instance
function runUpdateVisitorOnLogin(Request $request, ?User $user = null): void
{
    /** @var UpdateVisitorOnLogin $mw */
    $mw = app()->make(UpdateVisitorOnLogin::class);
    /** @var StartSession $startSession */
    $startSession = app()->make(StartSession::class);

    // Attach user resolver
    $request->setUserResolver(static fn() => $user);

    // Run StartSession first to ensure $request->session() is available, then our middleware
    $startSession->handle($request, function ($req) use ($mw) {
        $mw->handle($req, static fn() => response('ok'));

        return response('ok');
    });
}

function createAnonPageView(array $overrides = []): PageView
{
    return PageView::query()->create(array_merge([
        'user_id' => null,
        'visitor_id' => $overrides['visitor_id'] ?? null,
        'session_id' => $overrides['session_id'] ?? 'sess-1',
        'viewable_type' => $overrides['viewable_type'] ?? 'x',
        'viewable_id' => $overrides['viewable_id'] ?? 1,
        'ip_address' => $overrides['ip_address'] ?? '127.0.0.1',
        'user_agent' => $overrides['user_agent'] ?? 'UA',
        'fingerprint' => $overrides['fingerprint'] ?? null,
    ], $overrides));
}

it('syncs anonymous page views by visitor_id only, without touching others', function () {
    $user = User::factory()->create();

    // Anonymous page views: two with matching visitor_id, one with different visitor_id.
    $matchA = createAnonPageView(['visitor_id' => 'v-123']);
    $matchB = createAnonPageView(['visitor_id' => 'v-123']);
    $other = createAnonPageView(['visitor_id' => 'v-other']);

    // Build request with matching visitor_id cookie
    $request = Request::create('/dummy', 'GET');
    $request->cookies->set('visitor_id', 'v-123');
    session()->start();
    runUpdateVisitorOnLogin($request, $user);

    // Refresh models
    $matchA->refresh();
    $matchB->refresh();
    $other->refresh();

    expect($matchA->user_id)->toBe($user->id)
        ->and($matchB->user_id)->toBe($user->id)
        ->and($other->user_id)->toBeNull();
});

it('syncs anonymous page views by fingerprint when visitor_id cookie is missing', function () {
    $user = User::factory()->create();

    // Prepare deterministic request environment to compute fingerprint
    $ip = '192.168.1.123';
    $ua = 'Mozilla/5.0 (Test)';
    $al = 'en-US';

    // Compute expected fingerprint the same way as FingerprintGenerator
    $bucket = (new FingerprintGenerator)->normalizeIpToBucket($ip);
    $expectedFingerprint = hash('sha256', implode('|', [$bucket, $ua, $al]));

    // Seed anonymous views: one with matching fingerprint, one different
    $match = createAnonPageView(['fingerprint' => $expectedFingerprint]);
    $other = createAnonPageView(['fingerprint' => 'different-fp']);

    // Make request without visitor_id cookie, but with headers that yield the same fingerprint
    // Build request without visitor cookie but with headers producing the expected fingerprint
    $request = Request::create('/dummy', 'GET', [], [], [], [
        'REMOTE_ADDR' => $ip,
        'HTTP_USER_AGENT' => $ua,
        'HTTP_ACCEPT_LANGUAGE' => $al,
    ]);
    session()->start();
    runUpdateVisitorOnLogin($request, $user);

    $match->refresh();
    $other->refresh();

    expect($match->user_id)->toBe($user->id)
        ->and($other->user_id)->toBeNull();
});

it('does not run twice within the same session (session flag set after first sync)', function () {
    $user = User::factory()->create();

    // First, seed one match by visitor_id, perform request — this sets the session flag.
    $first = createAnonPageView(['visitor_id' => 'v-abc']);

    $request = Request::create('/dummy', 'GET');
    $request->cookies->set('visitor_id', 'v-abc');
    session()->start();
    runUpdateVisitorOnLogin($request, $user);

    $first->refresh();
    expect($first->user_id)->toBe($user->id);

    // Now create another matching anonymous row in the same session and hit again — should NOT sync due to flag.
    $second = createAnonPageView(['visitor_id' => 'v-abc']);

    // Reuse the same session store instance and cookie; flag should block second sync
    runUpdateVisitorOnLogin($request, $user);

    $second->refresh();
    expect($second->user_id)->toBeNull();
});
