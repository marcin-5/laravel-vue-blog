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

    private string $domain;

    private string $domainSecondary;

    public function test_welcome_page_links_contain_correct_blog_slugs()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();

        Blog::factory()->create([
            'user_id' => $user->id,
            'name' => 'Polski Blog',
            'slug' => 'polski-blog',
            'locale' => 'pl',
            'is_published' => true,
        ]);

        Blog::factory()->create([
            'user_id' => $user->id,
            'name' => 'English Blog',
            'slug' => 'english-blog',
            'locale' => 'en',
            'is_published' => true,
        ]);

        // On Polish domain, we should see the Polish blog
        $this
            ->get('http://' . $this->domain . '/')
            ->assertInertia(fn($page) => $page
                ->has('blogs', 1)
                ->where('blogs.0.slug', 'polski-blog')
                ->where('blogs.0.url', 'http://polski-blog.' . $this->domain),
            );

        // On English domain, we should see the English blog
        $this
            ->get('http://' . $this->domainSecondary . '/')
            ->assertInertia(fn($page) => $page
                ->has('blogs', 1)
                ->where('blogs.0.slug', 'english-blog')
                ->where('blogs.0.url', 'http://english-blog.' . $this->domainSecondary),
            );
    }

    public function test_blog_landing_page_post_links_use_correct_domain()
    {
        $user = User::factory()->create();

        $plBlog = Blog::factory()->create([
            'user_id' => $user->id,
            'name' => 'Polski Blog',
            'slug' => 'polski-blog',
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

        // Verify old URL redirects to subdomain
        $this
            ->get('http://' . $this->domain . '/polski-blog')
            ->assertRedirect('http://polski-blog.' . $this->domain);

        // Verify we can access the landing page and posts are correct on subdomain
        $this
            ->get('http://polski-blog.' . $this->domain)
            ->assertStatus(200)
            ->assertInertia(fn($page) => $page
                ->has('posts', 1)
                ->where('posts.0.slug', 'polski-post'),
            );

        // Verify that route() helper generates the correct absolute URL for the Polish domain
        $this->assertEquals(
            'http://polski-blog.' . $this->domain . '/polski-post',
            route(
                'blog.public.post',
                ['blog' => 'polski-blog', 'postSlug' => 'polski-post', 'mainDomain' => $this->domain],
            ),
        );

        // English domain
        $enBlog = Blog::factory()->create([
            'user_id' => $user->id,
            'name' => 'English Blog',
            'slug' => 'english-blog',
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

        // Verify old URL redirects to subdomain
        $this
            ->get('http://' . $this->domainSecondary . '/english-blog')
            ->assertRedirect('http://english-blog.' . $this->domainSecondary);

        // Verify we can access the landing page and posts are correct on subdomain
        $this
            ->get('http://english-blog.' . $this->domainSecondary)
            ->assertStatus(200)
            ->assertInertia(fn($page) => $page
                ->has('posts', 1)
                ->where('posts.0.slug', 'english-post'),
            );

        // Test context for English domain - route() should point to the English domain
        $this->prepareForDomain($this->domainSecondary);
        $this->assertEquals(
            'http://english-blog.' . $this->domainSecondary . '/english-post',
            route(
                'blog.public.post',
                ['blog' => 'english-blog', 'postSlug' => 'english-post', 'mainDomain' => $this->domainSecondary],
            ),
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

        $this->domain = 'osobliwy.localhost';
        $this->domainSecondary = 'peculiarmatters.localhost';

        Config::set('app.domain', $this->domain);
        Config::set('app.domain_secondary', $this->domainSecondary);

        Config::set('app.domain_locales', [
            $this->domain => 'pl',
            $this->domainSecondary => 'en',
        ]);
        Config::set('app.supported_locales', ['pl', 'en']);
    }
}
