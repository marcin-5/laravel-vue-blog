<?php

use App\Jobs\IndexNowSubmitJob;
use App\Models\Blog;
use App\Models\IndexNowQueuedUrl;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
    config(['services.indexnow.key' => 'test-key']);
    config(['app.url' => 'https://example.org']);
});

test('it queues old and new url for submission when blog slug changes', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'old-blog-slug',
        'is_published' => true,
    ]);
    $blog->refresh();

    $oldUrl = route('blog.public.landing', ['blog' => $blog->slug, 'mainDomain' => $blog->main_domain]);
    $allUrls = IndexNowQueuedUrl::pluck('url')->toArray();
    if (!in_array($oldUrl, $allUrls)) {
        throw new Exception('Old URL not found in queue. Available URLs: ' . implode(', ', $allUrls));
    }
    expect(IndexNowQueuedUrl::where('url', $oldUrl)->exists())->toBeTrue();

    // Clear queue to focus on update
    IndexNowQueuedUrl::truncate();

    $blog->update(['slug' => 'new-blog-slug']);

    $newUrl = route('blog.public.landing', ['blog' => 'new-blog-slug', 'mainDomain' => $blog->main_domain]);

    expect(IndexNowQueuedUrl::where('url', $newUrl)->exists())->toBeTrue();
    expect(IndexNowQueuedUrl::where('url', $oldUrl)->exists())->toBeTrue();
});

test('it queues old and new url for submission when post slug changes', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'blog_id' => $blog->id,
        'slug' => 'old-post-slug',
        'is_published' => true,
        'visibility' => 'public',
    ]);

    $oldUrl = route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => 'old-post-slug', 'mainDomain' => $blog->main_domain]);
    expect(IndexNowQueuedUrl::where('url', $oldUrl)->exists())->toBeTrue();

    // Clear queue
    IndexNowQueuedUrl::truncate();

    $post->update(['slug' => 'new-post-slug']);

    $newUrl = route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => 'new-post-slug', 'mainDomain' => $blog->main_domain]);

    expect(IndexNowQueuedUrl::where('url', $newUrl)->exists())->toBeTrue();
    expect(IndexNowQueuedUrl::where('url', $oldUrl)->exists())->toBeTrue();
});

test('it queues old urls for all posts when blog slug changes', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'old-blog-slug',
        'is_published' => true,
    ]);
    $blog->refresh();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'blog_id' => $blog->id,
        'slug' => 'post-slug',
        'is_published' => true,
        'visibility' => 'public',
    ]);
    $post->refresh();

    $oldPostUrl = route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $post->slug, 'mainDomain' => $blog->main_domain]);
    expect(IndexNowQueuedUrl::where('url', $oldPostUrl)->exists())->toBeTrue();

    // Clear queue
    IndexNowQueuedUrl::truncate();

    $oldBlogSlug = $blog->slug;
    $blog->update(['slug' => 'new-blog-slug']);

    $newBlogUrl = route('blog.public.landing', ['blog' => 'new-blog-slug', 'mainDomain' => $blog->main_domain]);
    $oldBlogUrl = route('blog.public.landing', ['blog' => $oldBlogSlug, 'mainDomain' => $blog->main_domain]);
    $newPostUrl = route('blog.public.post', ['blog' => 'new-blog-slug', 'postSlug' => 'post-slug', 'mainDomain' => $blog->main_domain]);
    $oldPostUrl = route('blog.public.post', ['blog' => $oldBlogSlug, 'postSlug' => 'post-slug', 'mainDomain' => $blog->main_domain]);

    expect(IndexNowQueuedUrl::where('url', $newBlogUrl)->exists())->toBeTrue();
    expect(IndexNowQueuedUrl::where('url', $oldBlogUrl)->exists())->toBeTrue();
    expect(IndexNowQueuedUrl::where('url', $newPostUrl)->exists())->toBeTrue();
    expect(IndexNowQueuedUrl::where('url', $oldPostUrl)->exists())->toBeTrue();
});

test('it queues current and old about urls when blog slug changes', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'old-blog-slug',
        'is_published' => true,
    ]);
    $oldAboutUrl = route('blog.public.about', [
        'blog' => $blog->slug,
        'mainDomain' => $blog->main_domain,
    ]);
    IndexNowQueuedUrl::truncate();

    $blog->update(['slug' => 'new-blog-slug']);

    $newAboutUrl = route('blog.public.about', [
        'blog' => $blog->slug,
        'mainDomain' => $blog->main_domain,
    ]);

    expect(IndexNowQueuedUrl::where('url', $newAboutUrl)->exists())->toBeTrue();
    expect(IndexNowQueuedUrl::where('url', $oldAboutUrl)->exists())->toBeTrue();
});

test('it queues about url when about content changes', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'is_published' => true,
    ]);
    IndexNowQueuedUrl::truncate();

    $blog->update(['about' => 'Updated about content']);

    $aboutUrl = route('blog.public.about', [
        'blog' => $blog->slug,
        'mainDomain' => $blog->main_domain,
    ]);

    expect(IndexNowQueuedUrl::where('url', $aboutUrl)->exists())->toBeTrue();
});

test('it keeps queued urls when one IndexNow host payload fails', function () {
    $firstUrl = 'https://enneagram.osobliwy.blog';
    $secondUrl = 'https://enneagram.peculiarmatters.blog';
    IndexNowQueuedUrl::create(['url' => $firstUrl]);
    IndexNowQueuedUrl::create(['url' => $secondUrl]);
    Cache::forget('index_now_next_run');
    config(['app.indexnow_test_fail_host' => 'enneagram.peculiarmatters.blog']);

    app(IndexNowSubmitJob::class)->handle(app(\App\Services\IndexNowService::class));

    expect(IndexNowQueuedUrl::count())->toBe(2);
});
