<?php

use App\Mail\NewsletterPostNotification;
use App\Models\NewsletterLog;
use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

it('sends newsletter with newly attached extensions', function () {
    Mail::fake();

    $blog = createBlog();
    $subscription = createSubscription($blog, [
        'frequency' => 'daily',
        'send_time' => now()->format('H:i'),
        'send_time_weekend' => now()->format('H:i'),
    ]);

    // Public post
    $mainPost = createPost($blog, [
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDays(2), // Older than newsletter window
    ]);

    // Extension created long ago but attached now
    $extension = createPost($blog, [
        'visibility' => Post::VIS_EXTENSION,
        'published_at' => now()->subDays(5),
    ]);

    // Manually attach to control created_at on pivot
    DB::table('post_extensions')->insert([
        'post_id' => $mainPost->id,
        'extension_post_id' => $extension->id,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('newsletter:send daily')
        ->assertSuccessful();

    Mail::assertSent(NewsletterPostNotification::class, function ($mail) use ($subscription, $extension, $mainPost) {
        $posts = $mail->data->first()['posts'];
        $post = $posts->first();

        return $mail->hasTo($subscription->email) &&
            $posts->count() === 1 &&
            $post->id === $extension->id &&
            $post->parentPosts->contains($mainPost->id);
    });

    expect(NewsletterLog::where('post_id', $extension->id)->exists())->toBeTrue();
});

it('does not send extension if it was attached long ago', function () {
    Mail::fake();

    $blog = createBlog();
    createSubscription($blog, [
        'frequency' => 'daily',
        'send_time' => now()->format('H:i'),
    ]);

    $mainPost = createPost($blog, ['visibility' => Post::VIS_PUBLIC]);
    $extension = createPost($blog, ['visibility' => Post::VIS_EXTENSION]);

    DB::table('post_extensions')->insert([
        'post_id' => $mainPost->id,
        'extension_post_id' => $extension->id,
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $this->artisan('newsletter:send daily')->assertSuccessful();

    Mail::assertNothingSent();
});

it('does not send extension if attached to non-public post', function () {
    Mail::fake();

    $blog = createBlog();
    createSubscription($blog, [
        'frequency' => 'daily',
        'send_time' => now()->format('H:i'),
    ]);

    // Restricted post (not for public view in newsletter context)
    $mainPost = createPost($blog, [
        'visibility' => Post::VIS_RESTRICTED,
    ]);

    $extension = createPost($blog, ['visibility' => Post::VIS_EXTENSION]);

    DB::table('post_extensions')->insert([
        'post_id' => $mainPost->id,
        'extension_post_id' => $extension->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('newsletter:send daily')->assertSuccessful();

    Mail::assertNothingSent();
});

it('uses attached_at alias', function () {
    $blog = createBlog();
    $mainPost = createPost($blog);
    $extension = createPost($blog, ['visibility' => Post::VIS_EXTENSION]);

    $mainPost->extensions()->attach($extension->id);

    $extensionWithPivot = $mainPost->extensions()->first();

    expect($extensionWithPivot->attached_at)->not->toBeNull()
        ->and($extensionWithPivot->attached_at)->toBeInstanceOf(Carbon::class);
});
