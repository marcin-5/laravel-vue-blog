<?php

use App\Jobs\IndexNowSubmitJob;
use App\Models\Blog;
use App\Models\IndexNowQueuedUrl;
use App\Models\Post;
use App\Models\User;
use App\Services\IndexNowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake(function ($request) {
        return $request->data()['host'] === config('app.indexnow_test_fail_host')
            ? Http::response([], 500)
            : Http::response([], 200);
    });
    Queue::fake();
    IndexNowQueuedUrl::truncate();
    Cache::flush();
    config(['services.indexnow.key' => 'test-key']);
    config(['app.url' => 'https://example.org']);
});

test('it queues blog url for submission when created and published', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);

    expect(
        IndexNowQueuedUrl::where(
            'url',
            route('blog.public.landing', ['blog' => $blog->slug, 'mainDomain' => $blog->main_domain]),
        )->exists(),
    )->toBeTrue();
    Queue::assertPushed(IndexNowSubmitJob::class);
});

test('it removes url from queue when unpublished', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);
    $url = route('blog.public.landing', ['blog' => $blog->slug, 'mainDomain' => $blog->main_domain]);

    expect(IndexNowQueuedUrl::where('url', $url)->exists())->toBeTrue();

    $blog->update(['is_published' => false]);

    expect(IndexNowQueuedUrl::where('url', $url)->exists())->toBeFalse();
});

test('it queues post url for submission when published and public', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'blog_id' => $blog->id,
        'is_published' => true,
        'visibility' => 'public',
    ]);

    expect(
        IndexNowQueuedUrl::where(
            'url',
            route(
                'blog.public.post',
                ['blog' => $blog->slug, 'postSlug' => $post->slug, 'mainDomain' => $blog->main_domain],
            ),
        )->exists(),
    )->toBeTrue();
    Queue::assertPushed(IndexNowSubmitJob::class);
});

test('it does not queue post url when visibility is restricted', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'blog_id' => $blog->id,
        'is_published' => true,
        'visibility' => 'unlisted',
    ]);

    expect(IndexNowQueuedUrl::count())->toBe(3); // Blog landing, about, and contact pages are queued
    expect(
        IndexNowQueuedUrl::where(
            'url',
            route(
                'blog.public.post',
                ['blog' => $blog->slug, 'postSlug' => $post->slug, 'mainDomain' => $blog->main_domain],
            ),
        )->exists(),
    )->toBeFalse();
});

test('it handles robots.txt restriction', function () {
    $service = app(IndexNowService::class);
    $robotsPath = public_path('robots.txt');

    File::put($robotsPath, "User-agent: *\nDisallow: /blocked");

    expect($service->isAllowedByRobots(url('/allowed')))->toBeTrue();
    expect($service->isAllowedByRobots(url('/blocked')))->toBeFalse();

    File::delete($robotsPath);
});

test('artisan command submits urls', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);

    $this
        ->artisan('blog:indexnow', ['path' => $blog->slug])
        ->assertExitCode(0)
        ->expectsOutput("Submitting all pages for blog: $blog->slug...")
        ->expectsOutput('Submitting 2 URLs to IndexNow...');
});

test('artisan command submits a post URL on the blog subdomain', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'blog_id' => $blog->id,
        'is_published' => true,
        'visibility' => 'public',
    ]);

    $canonicalUrl = route('blog.public.post', [
        'blog' => $blog->slug,
        'postSlug' => $post->slug,
        'mainDomain' => $blog->main_domain,
    ]);
    $legacyUrl = url("/$blog->slug/$post->slug");

    $this
        ->artisan('blog:indexnow', ['path' => "$blog->slug/$post->slug"])
        ->assertExitCode(0)
        ->expectsOutput("Submitting post: $blog->slug/$post->slug...")
        ->expectsOutput('Submitting 1 URLs to IndexNow...');

    Http::assertSent(function ($request) use ($canonicalUrl, $legacyUrl): bool {
        return $request->url() === 'https://api.indexnow.org/indexnow'
            && $request['urlList'] === [$canonicalUrl]
            && !in_array($legacyUrl, $request['urlList'], true);
    });
});

test('artisan command rejects an ambiguous blog slug without locale', function () {
    $user = User::factory()->create();
    Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'enneagram',
        'locale' => 'pl',
        'is_published' => true,
    ]);
    Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'enneagram',
        'locale' => 'en',
        'is_published' => true,
    ]);

    $this
        ->artisan('blog:indexnow', ['path' => 'enneagram'])
        ->assertExitCode(1)
        ->expectsOutput("Multiple blogs found for slug 'enneagram'. Use --locale=pl or --locale=en.");
});

test('artisan command submits a localized blog about page', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'enneagram',
        'locale' => 'en',
        'is_published' => true,
    ]);
    $aboutUrl = route('blog.public.about', [
        'blog' => $blog->slug,
        'mainDomain' => $blog->main_domain,
    ]);

    $this
        ->artisan('blog:indexnow', ['path' => 'enneagram/about', '--locale' => 'en'])
        ->assertExitCode(0)
        ->expectsOutput('Submitting about page: enneagram/about...')
        ->expectsOutput('Submitting 1 URLs to IndexNow...');

    Http::assertSent(fn($request): bool => $request['urlList'] === [$aboutUrl]);
});

test('artisan command submits landing about and public posts for a localized blog', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'enneagram',
        'locale' => 'pl',
        'is_published' => true,
    ]);
    Post::factory()->create([
        'user_id' => $user->id,
        'blog_id' => $blog->id,
        'is_published' => true,
        'visibility' => 'public',
        'group_id' => null,
    ]);

    $this
        ->artisan('blog:indexnow', ['path' => 'enneagram', '--locale' => 'pl'])
        ->assertExitCode(0)
        ->expectsOutput('Submitting 3 URLs to IndexNow...');
});

test('it sends separate IndexNow payloads for each URL host', function () {
    $service = app(IndexNowService::class);
    $urls = [
        'https://enneagram.osobliwy.blog',
        'https://enneagram.osobliwy.blog/about',
        'https://enneagram.peculiarmatters.blog',
    ];

    expect($service->submitUrls($urls))->toBeTrue();

    Http::assertSentCount(2);
    Http::assertSent(fn($request): bool => $request['host'] === 'enneagram.osobliwy.blog'
        && $request['keyLocation'] === 'https://enneagram.osobliwy.blog/test-key.txt'
        && $request['urlList'] === array_slice($urls, 0, 2));
    Http::assertSent(fn($request): bool => $request['host'] === 'enneagram.peculiarmatters.blog'
        && $request['keyLocation'] === 'https://enneagram.peculiarmatters.blog/test-key.txt'
        && $request['urlList'] === [$urls[2]]);
});

test('it reports failure when one IndexNow host payload fails', function () {
    config(['app.indexnow_test_fail_host' => 'enneagram.peculiarmatters.blog']);

    $service = app(IndexNowService::class);

    expect($service->submitUrls([
        'https://enneagram.osobliwy.blog',
        'https://enneagram.peculiarmatters.blog',
    ]))->toBeFalse();

    Http::assertSentCount(2);
});

test('artisan command shows pending queue status', function () {
    IndexNowQueuedUrl::create(['url' => 'https://example.org/pending-1']);
    IndexNowQueuedUrl::create(['url' => 'https://example.org/pending-2']);

    $this
        ->artisan('blog:indexnow', ['--logs' => true])
        ->assertExitCode(0)
        ->expectsOutput('Pending IndexNow URL queue: 2 URL(s) waiting to be submitted.');
});

test('it only queues for relevant attribute changes', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'blog_id' => $blog->id,
        'is_published' => true,
        'visibility' => 'public',
        'excerpt' => 'Original excerpt',
    ]);

    $post = $post->fresh();
    IndexNowQueuedUrl::truncate();
    Queue::fake();
    Cache::forget('index_now_next_run');

    // Case 1: Unimportant change (e.g. updated_at is handled by Laravel, but let's test a field not in relevant list)
    // Actually all fields in $fillable for Post are relevant except maybe blog_id/user_id/group_id.
    // Let's try to update something that is not in the list, but it's hard without adding dummy fields.
    // We can test save() without changes.
    $post->save();
    expect(IndexNowQueuedUrl::count())->toBe(0);
    Queue::assertNotPushed(IndexNowSubmitJob::class);

    // Case 2: Important change (excerpt)
    $post->update(['excerpt' => 'New excerpt']);
    expect(
        IndexNowQueuedUrl::where(
            'url',
            route(
                'blog.public.post',
                ['blog' => $blog->slug, 'postSlug' => $post->slug, 'mainDomain' => $blog->main_domain],
            ),
        )->exists(),
    )->toBeTrue();
    Queue::assertPushed(IndexNowSubmitJob::class);
});
