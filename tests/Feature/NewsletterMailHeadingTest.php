<?php

use App\Mail\NewsletterPostNotification;
use App\Models\Blog;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders headings correctly for multiple posts in the same blog', function () {
    $blog1 = Blog::factory()->create(['name' => 'Test Blog 1']);

    $post1 = Post::factory()->create([
        'blog_id' => $blog1->id,
        'title' => 'First Post Title',
        'excerpt' => 'First Excerpt',
    ]);

    $post2 = Post::factory()->create([
        'blog_id' => $blog1->id,
        'title' => 'Second Post Title',
        'excerpt' => 'Second Excerpt',
    ]);

    $data = collect([
        [
            'blog' => $blog1,
            'posts' => collect([$post1, $post2]),
        ],
    ]);

    $mailable = new NewsletterPostNotification($data);

    $html = $mailable->render();

    // Check if "# ", "## " and "### " are not present in the rendered HTML
    // and there are appropriate HTML tags
    expect($html)->not->toContain('# ')
        ->and($html)->not->toContain('## ')
        ->and($html)->not->toContain('### ')
        ->and($html)->toContain('<h2')
        ->and($html)->toContain('Blog: Test Blog 1')
        ->and($html)->toContain('<h3')
        ->and($html)->toContain('First Post Title')
        ->and($html)->toContain('Second Post Title');

    // Count <h3> occurrences - should be 2
    $h3Count = substr_count($html, '<h3');
    expect($h3Count)->toBe(2);
});
