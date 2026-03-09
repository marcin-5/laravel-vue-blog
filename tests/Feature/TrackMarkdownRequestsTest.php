<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackMarkdownRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_markdown_request_and_increments_hits(): void
    {
        // Create a user and blog
        $user = User::factory()->create();
        $blog = Blog::factory()->create([
            'user_id' => $user->id,
            'is_published' => true,
        ]);

        // First request (should create a record)
        $this->get("/{$blog->slug}", [
            'Accept' => 'text/markdown',
        ])->assertStatus(200);

        $this->assertDatabaseHas('markdown_views', [
            'viewable_type' => $blog->getMorphClass(),
            'viewable_id' => $blog->id,
            'hits' => 1,
        ]);

        // Second request (should increase hits)
        $this->get("/{$blog->slug}", [
            'Accept' => 'text/markdown',
        ])->assertStatus(200);

        $this->assertDatabaseHas('markdown_views', [
            'viewable_type' => $blog->getMorphClass(),
            'viewable_id' => $blog->id,
            'hits' => 2,
        ]);
    }
}
