<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SitemapIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.domain', 'osobliwy.pl');
        Config::set('app.domain_secondary', 'osobliwy.com');
        Config::set('app.domain_locales', [
            'osobliwy.pl' => 'pl',
            'osobliwy.com' => 'en',
        ]);
        Config::set('app.supported_locales', ['pl', 'en']);
    }

    public function test_main_domain_sitemap_contains_only_system_links_and_blog_landings()
    {
        $user = User::factory()->create();

        $blogPl = Blog::factory()->create([
            'user_id' => $user->id,
            'slug' => 'blog-pl',
            'locale' => 'pl',
            'is_published' => true,
        ]);

        Post::factory()->create([
            'blog_id' => $blogPl->id,
            'user_id' => $user->id,
            'title' => 'Post na blogu PL',
            'is_published' => true,
            'visibility' => 'public',
        ]);

        $response = $this->get('http://osobliwy.pl/sitemap.xml');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Powinno zawierać stronę główną i landing bloga
        $this->assertStringContainsString('http://osobliwy.pl', $content);
        $this->assertStringContainsString('http://blog-pl.osobliwy.pl', $content);

        // NIE powinno zawierać posta bloga (według nowej strategii)
        $this->assertStringNotContainsString('Post na blogu PL', $content);
        // Uwaga: assertSee sprawdza tekst, w sitemapie są slugi/URL-e.
        $this->assertStringNotContainsString('post-na-blogu-pl', $content);
    }

    public function test_subdomain_sitemap_contains_only_specific_blog_links()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();

        $blog1 = Blog::factory()->create([
            'user_id' => $user->id,
            'slug' => 'blog1',
            'locale' => 'pl',
            'is_published' => true,
        ]);

        $blog2 = Blog::factory()->create([
            'user_id' => $user->id,
            'slug' => 'blog2',
            'locale' => 'pl',
            'is_published' => true,
        ]);

        $post1 = Post::factory()->create([
            'blog_id' => $blog1->id,
            'user_id' => $user->id,
            'slug' => 'post-blog-1',
            'is_published' => true,
            'visibility' => 'public',
        ]);

        $post2 = Post::factory()->create([
            'blog_id' => $blog2->id,
            'user_id' => $user->id,
            'slug' => 'post-blog-2',
            'is_published' => true,
            'visibility' => 'public',
        ]);

        $response = $this->get('http://blog1.osobliwy.pl/sitemap.xml');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Powinno zawierać linki do blog1
        $this->assertStringContainsString('http://blog1.osobliwy.pl', $content);
        $this->assertStringContainsString('post-blog-1', $content);

        // NIE powinno zawierać linków do blog2 ani strony głównej katalogu
        $this->assertStringNotContainsString('http://blog2.osobliwy.pl', $content);
        $this->assertStringNotContainsString('post-blog-2', $content);
        // Katalog główny (landing page katalogu) nie powinien być w sitemapie subdomeny
        // Tutaj trzeba uważać, bo URL katalogu to http://osobliwy.pl
        $this->assertStringNotContainsString('<loc>http://osobliwy.pl</loc>', $content);
    }

    public function test_sitemap_does_not_contain_group_links()
    {
        $user = User::factory()->create();

        $blog = Blog::factory()->create([
            'user_id' => $user->id,
            'slug' => 'public-blog',
            'is_published' => true,
        ]);

        $group = Group::factory()->create([
            'user_id' => $user->id,
            'slug' => 'secret-group',
        ]);

        Post::factory()->create([
            'blog_id' => $blog->id,
            'group_id' => $group->id, // Post przypisany i do bloga i do grupy? (potencjalnie problematyczne)
            'user_id' => $user->id,
            'slug' => 'mixed-post',
            'is_published' => true,
            'visibility' => 'public',
        ]);

        $groupPost = Post::factory()->create([
            'group_id' => $group->id,
            'blog_id' => null,
            'user_id' => $user->id,
            'slug' => 'group-only-post',
            'is_published' => true,
            'visibility' => 'public',
        ]);

        $response = $this->get('http://osobliwy.pl/sitemap.xml');
        $content = $response->getContent();

        // Nie powinno być linków z prefiksem /_/ ani slugów grup
        $this->assertStringNotContainsString('/_/', $content);
        $this->assertStringNotContainsString('secret-group', $content);
        $this->assertStringNotContainsString('group-only-post', $content);
        $this->assertStringNotContainsString('mixed-post', $content); // Posty z group_id powinny być wykluczone
    }

    public function test_sitemap_cache_is_cleared_when_tag_is_added()
    {
        $user = User::factory()->create();
        $blog = Blog::factory()->create([
            'user_id' => $user->id,
            'slug' => 'test-blog',
            'is_published' => true,
        ]);

        // First request to prime cache
        $this->get('http://test-blog.osobliwy.pl/sitemap.xml');
        $cacheKey = "sitemap_blog_{$blog->id}";
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has($cacheKey));

        // Create a tag
        \App\Models\Tag::create([
            'blog_id' => $blog->id,
            'name' => 'New Tag',
            'slug' => 'new-tag',
        ]);

        // Cache should be cleared
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has($cacheKey));

        // New request should contain the tag
        $response = $this->get('http://test-blog.osobliwy.pl/sitemap.xml');
        $this->assertStringContainsString('new-tag', $response->getContent());
    }
}
