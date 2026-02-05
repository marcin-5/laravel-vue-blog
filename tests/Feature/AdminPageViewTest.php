<?php

use App\Jobs\StorePageView;
use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Cache::flush();

    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->blogger = User::factory()->create(['role' => User::ROLE_BLOGGER]);

    $this->blog = Blog::factory()->for($this->blogger)->create(['is_published' => true]);
    $this->post = Post::factory()->for($this->blog)->create([
        'published_at' => now()->subMinute(),
        'visibility' => 'public'
    ]);
});

it('counts views for logged in admin', function () {
    $this->actingAs($this->admin)
        ->get("/{$this->blog->slug}/{$this->post->slug}")
        ->assertStatus(200);

    Queue::assertPushed(StorePageView::class, 1);
});

it('counts views for logged in blogger (author)', function () {
    $this->actingAs($this->blogger)
        ->get("/{$this->blog->slug}/{$this->post->slug}")
        ->assertStatus(200);

    Queue::assertPushed(StorePageView::class, 1);
});

it('actually increments redis when job is handled', function () {
    Queue::fake();

    $data = [
        'user_id' => $this->admin->id,
        'viewable_type' => $this->post->getMorphClass(),
        'viewable_id' => $this->post->id,
        'ip_address' => '127.0.0.1',
        'session_id' => 'test-session',
    ];

    PageView::query()->delete();

    new StorePageView($data)->handle();

    expect(PageView::count())->toBe(1);
});

it('does not count same user twice even if cache is cleared (database level unique)', function () {
    $data = [
        'user_id' => $this->admin->id,
        'viewable_type' => $this->post->getMorphClass(),
        'viewable_id' => $this->post->id,
        'ip_address' => '127.0.0.1',
        'session_id' => 'test-session',
    ];

    PageView::query()->delete();

    new StorePageView($data)->handle();
    expect(PageView::count())->toBe(1);

    // Handle again
    new StorePageView($data)->handle();
    expect(PageView::count())->toBe(1); // Should not increase
});
