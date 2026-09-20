<?php

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Support\Facades\Event;
use RuntimeException;

it('rolls back a thread when its initial comment insertion fails', function () {
    $author = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'user_id' => $author->id,
        'allow_comments' => true,
    ]);
    $event = 'eloquent.creating: ' . Comment::class;
    $listener = function (): void {
        throw new RuntimeException('Initial comment insertion failed.');
    };

    Event::listen($event, $listener);

    try {
        expect(fn() => app(CommentService::class)->createThread($post, $author, [
            'title' => 'Transactional topic',
            'visibility' => 'public',
            'content' => 'Transactional opening comment',
        ]))->toThrow(RuntimeException::class, 'Initial comment insertion failed.');
    } finally {
        Event::forget($event);
    }

    $this->assertDatabaseMissing('threads', [
        'post_id' => $post->id,
        'title' => 'Transactional topic',
    ]);
});

it('includes group and comment translations in both supported locales', function (string $locale) {
    $translations = json_decode(
        file_get_contents(base_path("resources/lang/{$locale}/public.json")),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect(data_get($translations, 'group.posts_list.title'))->toBe($locale === 'pl' ? 'Wpisy na grupie' : 'Group posts')
        ->and(data_get($translations, 'comments.title'))->not->toBeEmpty()
        ->and(data_get($translations, 'comments.create_thread.title'))->not->toBeEmpty()
        ->and(data_get($translations, 'comments.errors.reply'))->not->toBeEmpty();
})->with([
    'English' => 'en',
    'Polish' => 'pl',
]);
