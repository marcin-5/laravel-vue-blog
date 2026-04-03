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
});

test('it queues blog url for submission when created and published', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);

    expect(IndexNowQueuedUrl::where('url', route('blog.public.landing', $blog->slug))->exists())->toBeTrue();
    Queue::assertPushed(IndexNowSubmitJob::class);
});

test('it removes url from queue when unpublished', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);
    $url = route('blog.public.landing', $blog->slug);

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

    expect(IndexNowQueuedUrl::where('url', route('blog.public.post', [$blog->slug, $post->slug]))->exists())->toBeTrue(
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
    expect(IndexNowQueuedUrl::where('url', route('blog.public.post', [$blog->slug, $post->slug]))->exists())->toBeFalse(
    );
});

test('it handles robots.txt restriction', function () {
    $service = new IndexNowService;
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
        ->expectsOutput("Submitting all pages for blog: {$blog->slug}...");
});
