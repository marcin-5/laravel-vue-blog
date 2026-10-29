<?php

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('it lists only public published standalone post urls in newest-first order', function () {
    config([
        'app.domain' => 'example.test',
        'app.domain_secondary' => 'example.en',
        'app.domain_locales' => [
            'example.test' => 'pl',
            'example.en' => 'en',
        ],
    ]);

    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'sample',
        'locale' => 'pl',
        'is_published' => true,
    ]);
    $olderPost = Post::factory()->create([
        'blog_id' => $blog->id,
        'slug' => 'older-post',
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDays(2),
    ]);
    $newerPost = Post::factory()->create([
        'blog_id' => $blog->id,
        'slug' => 'newer-post',
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);
    Post::factory()->create([
        'blog_id' => $blog->id,
        'slug' => 'unlisted-post',
        'visibility' => Post::VIS_UNLISTED,
        'published_at' => now()->subDay(),
    ]);
    Post::factory()->create([
        'blog_id' => $blog->id,
        'slug' => 'future-post',
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->addDay(),
    ]);
    Post::factory()->create([
        'blog_id' => $blog->id,
        'group_id' => Group::factory()->create(['user_id' => $user->id])->id,
        'slug' => 'group-post',
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);

    $exitCode = Artisan::call('blog:post-urls', ['host' => 'sample.example.test']);
    $expectedUrls = [
        $newerPost->public_url,
        $olderPost->public_url,
    ];

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toBe(implode(PHP_EOL, $expectedUrls) . PHP_EOL);
});

test('it fails when the blog subdomain is not found', function () {
    $this->artisan('blog:post-urls', ['host' => 'missing.example.test'])
        ->expectsOutput('Blog subdomain not found.')
        ->assertExitCode(1);
});
