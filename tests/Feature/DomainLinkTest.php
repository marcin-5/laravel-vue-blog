<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DomainLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_links_contain_correct_blog_slugs()
    {
        $user = User::factory()->create();

        Blog::factory()->create([
            'user_id' => $user->id,
            'name' => 'Polski Blog',
            'locale' => 'pl',
            'is_published' => true,
        ]);

        Blog::factory()->create([
            'user_id' => $user->id,
            'name' => 'English Blog',
            'locale' => 'en',
            'is_published' => true,
        ]);

        // On Polish domain, we should see the Polish blog
        $this
            ->get('http://blog.pl/')
            ->assertInertia(fn($page) => $page
                ->has('blogs', 1)
                ->where('blogs.0.slug', 'polski-blog'),
            );

        // On English domain, we should see the English blog
        $this
            ->get('http://blog.com/')
            ->assertInertia(fn($page) => $page
                ->has('blogs', 1)
                ->where('blogs.0.slug', 'english-blog'),
            );
    }

    public function test_blog_landing_page_post_links_use_correct_domain()
    {
        $user = User::factory()->create();

        $plBlog = Blog::factory()->create([
            'user_id' => $user->id,
            'name' => 'Polski Blog',
            'locale' => 'pl',
            'is_published' => true,
        ]);

        $plPost = Post::factory()->create([
            'blog_id' => $plBlog->id,
            'user_id' => $user->id,
            'title' => 'Polski Post',
            'slug' => 'polski-post',
            'is_published' => true,
            'visibility' => 'public',
        ]);

        // Verify we can access the landing page and posts are correct
        $this
            ->get('http://blog.pl/polski-blog')
            ->assertStatus(200)
            ->assertInertia(fn($page) => $page
                ->has('posts', 1)
                ->where('posts.0.slug', 'polski-post'),
            );

        // Verify that route() helper generates the correct absolute URL for the Polish domain
        $this->assertEquals(
            'http://blog.pl/polski-blog/polski-post',
            route('blog.public.post', ['blog' => 'polski-blog', 'postSlug' => 'polski-post']),
        );

        // English domain
        $enBlog = Blog::factory()->create([
            'user_id' => $user->id,
            'name' => 'English Blog',
            'locale' => 'en',
            'is_published' => true,
        ]);

        $enPost = Post::factory()->create([
            'blog_id' => $enBlog->id,
            'user_id' => $user->id,
            'title' => 'English Post',
            'slug' => 'english-post',
            'is_published' => true,
            'visibility' => 'public',
        ]);

        $this
            ->get('http://blog.com/english-blog')
            ->assertStatus(200)
            ->assertInertia(fn($page) => $page
                ->has('posts', 1)
                ->where('posts.0.slug', 'english-post'),
            );

        // Test context for English domain - route() should point to the English domain
        $this->prepareForDomain('blog.com');
        $this->assertEquals(
            'http://blog.com/english-blog/english-post',
            route('blog.public.post', ['blog' => 'english-blog', 'postSlug' => 'english-post']),
        );
    }

    private function prepareForDomain(string $domain)
    {
        // This simulates a request to the domain to set the host in the current request context
        $this->get('http://' . $domain . '/_test/locale');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.domain_locales', [
            'blog.pl' => 'pl',
            'blog.com' => 'en',
        ]);
        Config::set('app.supported_locales', ['pl', 'en']);
    }
}
