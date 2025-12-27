<?php

use App\Mail\NewsletterPostNotification;
use App\Models\Blog;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders headings correctly without leading spaces', function () {
    $blog = Blog::factory()->create(['name' => 'Test Blog']);
    $posts = Post::factory()->count(1)->create([
        'blog_id' => $blog->id,
        'title' => 'Test Post Title',
        'excerpt' => 'Test Excerpt',
    ]);

    $mailable = new NewsletterPostNotification($blog, $posts);

    $html = $mailable->render();

    // Check if the headings have indentations (in the rendered HTML, they should be h1, h2, etc. tags)
    // But in markdown, the problem is that spaces at the beginning of the line can change the interpretation.
    // If there are spaces before #, then Laravel Mail Components may not parse this as headings.

    // Check if the rendered HTML contains literal "# " or "## " (which would indicate they were not parsed).
    expect($html)->not->toContain('# Test Blog')
        ->and($html)->not->toContain('## Test Post Title');

    // Check if there are appropriate HTML tags
    expect($html)->toContain('<h1>Nowe wpisy na blogu: Test Blog</h1>')
        ->and($html)->toContain('<h2>Test Post Title</h2>');

    // Check if <br> is visible (it should not be as text)
    expect($html)->not->toContain('&lt;br&gt;');
});
