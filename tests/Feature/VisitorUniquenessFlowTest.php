<?php

use App\Enums\StatsRange;
use App\Http\Middleware\UpdateVisitorOnLogin;
use App\Jobs\StorePageView;
use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Services\StatsCriteria;
use App\Services\StatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function runLoginSyncMiddleware(Request $request, ?User $user = null): void
{
    /** @var UpdateVisitorOnLogin $mw */
    $mw = app()->make(UpdateVisitorOnLogin::class);
    /** @var StartSession $startSession */
    $startSession = app()->make(StartSession::class);

    $request->setUserResolver(static fn() => $user);
    $startSession->handle($request, function ($req) use ($mw) {
        $mw->handle($req, static fn() => response('ok'));

        return response('ok');
    });
}

it('keeps unique entries across anon → login → logout transitions', function () {
    Carbon::setTestNow('2025-01-15 12:00:00');
    // Prepare content: one blog + one post within it
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id]);
    $post = Post::factory()->create(['blog_id' => $blog->id]);

    $blogMorph = $blog->getMorphClass();
    $postMorph = $post->getMorphClass();

    $visitorId = 'visitor-abc';

    // 1) Anonymous visits two pages: blog landing and post page
    dispatch_sync(new StorePageView([
        'user_id' => null,
        'visitor_id' => $visitorId,
        'session_id' => 's1',
        'viewable_type' => $blogMorph,
        'viewable_id' => $blog->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'UA',
        'fingerprint' => 'fp-1',
    ]));

    dispatch_sync(new StorePageView([
        'user_id' => null,
        'visitor_id' => $visitorId,
        'session_id' => 's1',
        'viewable_type' => $postMorph,
        'viewable_id' => $post->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'UA',
        'fingerprint' => 'fp-1',
    ]));

    expect(PageView::count())->toBe(2);

    // 2) Login: mapping + reassociation of previous anonymous rows to user_id
    $reqLogin = Request::create('/login-callback', 'GET');
    $reqLogin->cookies->set('visitor_id', $visitorId);
    session()->start();
    runLoginSyncMiddleware($reqLogin, $user);

    // Now both entries should have user_id set
    expect(PageView::where('user_id', $user->id)->count())->toBe(2);

    // 3) Revisit same pages while logged in → should not create new unique rows
    dispatch_sync(new StorePageView([
        'user_id' => $user->id,
        'visitor_id' => $visitorId,
        'session_id' => 's2',
        'viewable_type' => $blogMorph,
        'viewable_id' => $blog->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'UA',
        'fingerprint' => 'fp-2',
    ]));

    dispatch_sync(new StorePageView([
        'user_id' => $user->id,
        'visitor_id' => $visitorId,
        'session_id' => 's2',
        'viewable_type' => $postMorph,
        'viewable_id' => $post->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'UA',
        'fingerprint' => 'fp-2',
    ]));

    expect(PageView::count())->toBe(2);

    // 4) Logout and revisit as anonymous with same cookie
    // StorePageView maps visitor → user via VisitorLink, so still no new unique rows
    dispatch_sync(new StorePageView([
        'user_id' => null,
        'visitor_id' => $visitorId,
        'session_id' => 's3',
        'viewable_type' => $blogMorph,
        'viewable_id' => $blog->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'UA',
        'fingerprint' => 'fp-3',
    ]));

    dispatch_sync(new StorePageView([
        'user_id' => null,
        'visitor_id' => $visitorId,
        'session_id' => 's3',
        'viewable_type' => $postMorph,
        'viewable_id' => $post->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'UA',
        'fingerprint' => 'fp-3',
    ]));

    expect(PageView::count())->toBe(2);

    // Bonus: verify stats count distinct per visitor label (user name) within day range
    $stats = (new StatsService)->visitorViews(new StatsCriteria(range: StatsRange::Week));
    // We only have one effective visitor (the logged user), blog_views=1, post_views=1
    expect($stats)->toHaveCount(1);
    $row = $stats[0];
    expect($row['blog_views'])->toBe(1)->and($row['post_views'])->toBe(1);
});
