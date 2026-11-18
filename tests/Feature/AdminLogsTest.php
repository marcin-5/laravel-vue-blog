<?php

use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\IndexNowSubmission;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('only admins can open the logs page', function () {
    $user = User::factory()->create(['role' => UserRole::Blogger->value]);

    $this->actingAs($user)
        ->get(route('admin.logs.index'))
        ->assertForbidden();
});

test('admin sees newest submissions with related blog and post details', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $blog = Blog::factory()->create(['user_id' => $admin->id, 'name' => 'History blog']);
    $post = Post::factory()->create([
        'user_id' => $admin->id,
        'blog_id' => $blog->id,
        'title' => 'History post',
    ]);

    IndexNowSubmission::create([
        'url' => 'https://example.test/older',
        'blog_id' => $blog->id,
        'post_id' => $post->id,
        'succeeded' => false,
        'http_status' => 500,
        'submitted_at' => now()->subMinute(),
    ]);
    IndexNowSubmission::create([
        'url' => 'https://example.test/newer',
        'blog_id' => $blog->id,
        'post_id' => $post->id,
        'succeeded' => true,
        'http_status' => 200,
        'submitted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.logs.index'))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/admin/Logs')
            ->where('type', 'indexnow')
            ->where('perPage', 25)
            ->where('logs.data.0.url', 'https://example.test/newer')
            ->where('logs.data.0.blog.name', 'History blog')
            ->where('logs.data.0.post.title', 'History post')
            ->where('logs.data.1.url', 'https://example.test/older')
            ->where('logs.data.1.succeeded', false)
            ->where('logTypes.0.value', 'indexnow'),
        );
});

test('admin can choose page size and paginator preserves the selected type', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $submissions = collect(range(1, 12))->map(fn(int $number) => [
        'url' => "https://example.test/$number",
        'succeeded' => true,
        'submitted_at' => now()->subSeconds($number),
        'created_at' => now()->subSeconds($number),
        'updated_at' => now()->subSeconds($number),
    ])->all();
    IndexNowSubmission::insert($submissions);

    $this->actingAs($admin)
        ->get(route('admin.logs.index', ['type' => 'indexnow', 'per_page' => 10]))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->where('type', 'indexnow')
            ->where('perPage', 10)
            ->where('logs.data', fn($data) => count($data) === 10)
            ->where('logs.total', 12)
            ->where('logs.last_page', 2)
            ->where('logs.data.0.url', 'https://example.test/1')
            ->where('logs.next_page_url', fn(string $url) => str_contains($url, 'type=indexnow') && str_contains($url, 'per_page=10')),
        );
});

test('admin cannot select an unsupported page size', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);

    $this->actingAs($admin)
        ->get(route('admin.logs.index', ['per_page' => 20]))
        ->assertSessionHasErrors('per_page');
});

test('history retains model identifiers when related records no longer exist', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $submission = IndexNowSubmission::create([
        'url' => 'https://example.test/orphaned',
        'blog_id' => 98765,
        'post_id' => 98766,
        'succeeded' => true,
        'submitted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.logs.index'))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->where('logs.data.0.blog_id', 98765)
            ->where('logs.data.0.post_id', 98766)
            ->where('logs.data.0.blog', null)
            ->where('logs.data.0.post', null)
            ->where('logs.data.0.url', $submission->url),
        );
});
