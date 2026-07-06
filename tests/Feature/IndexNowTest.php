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
    Http::fake();
    Queue::fake();
    IndexNowQueuedUrl::truncate();
    Cache::flush();
    config(['services.indexnow.key' => 'test-key']);
    config(['app.url' => 'https://example.org']);
});

test('it queues blog url for submission when created and published', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);

    expect(IndexNowQueuedUrl::where('url', route('blog.public.landing', ['blog' => $blog->slug, 'mainDomain' => $blog->main_domain]))->exists())->toBeTrue();
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

    expect(IndexNowQueuedUrl::where('url', route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $post->slug, 'mainDomain' => $blog->main_domain]))->exists())->toBeTrue(
    );
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

    expect(IndexNowQueuedUrl::count())->toBe(1); // Only blog is queued
    expect(IndexNowQueuedUrl::where('url', route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $post->slug, 'mainDomain' => $blog->main_domain]))->exists())->toBeFalse(
    );
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
        ->expectsOutput("Submitting all pages for blog: {$blog->slug}...")
        ->expectsOutput('Submitting 1 URLs to IndexNow...');
});

test('artisan command shows pending queue status', function () {
    IndexNowQueuedUrl::create(['url' => 'https://example.org/pending-1']);
    IndexNowQueuedUrl::create(['url' => 'https://example.org/pending-2']);

    $this->artisan('blog:indexnow', ['--logs' => true])
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
        'excerpt' => 'Original excerpt'
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
    expect(IndexNowQueuedUrl::where('url', route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $post->slug, 'mainDomain' => $blog->main_domain]))->exists())->toBeTrue();
    Queue::assertPushed(IndexNowSubmitJob::class);
});
