<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DomainSitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.domain', 'blog.pl');
        Config::set('app.domain_secondary', 'blog.com');

        Config::set('app.domain_locales', [
            'blog.pl' => 'pl',
            'blog.com' => 'en',
        ]);
        Config::set('app.supported_locales', ['pl', 'en']);
    }

    public function test_robots_txt_points_to_correct_sitemap_based_on_domain()
    {
        $this->app->detectEnvironment(fn() => 'production');

        $response = $this->get('http://blog.pl/robots.txt');
        $response->assertStatus(200)
            ->assertSee('Sitemap: http://blog.pl/sitemap.xml');

        // Host might or might not have trailing slash depending on Laravel version/config
        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'Host: http://blog.pl') || str_contains($content, 'Host: http://blog.pl/'),
            'robots.txt should contain Host: http://blog.pl. Content: ' . $content
        );

        $this->get('http://blog.com/robots.txt')
            ->assertStatus(200)
            ->assertSee('Sitemap: http://blog.com/sitemap.xml');
    }

    public function test_sitemap_is_filtered_by_domain_locale()
    {
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

        $sitemapService = app(\App\Services\SitemapService::class);

        // Request sitemap from Polish domain
        $responsePl = $this->get('http://blog.pl/sitemap.xml');
        $responsePl->assertStatus(200);

        $contentPl = $responsePl->getContent();
        $this->assertStringContainsString('polski-blog', $contentPl);
        $this->assertStringNotContainsString('english-blog', $contentPl);

        // Request sitemap from English domain
        $responseEn = $this->get('http://blog.com/sitemap.xml');
        $responseEn->assertStatus(200);

        $contentEn = $responseEn->getContent();
        $this->assertStringContainsString('english-blog', $contentEn);
        $this->assertStringNotContainsString('polski-blog', $contentEn);
    }

    public function test_sitemap_contains_correct_domains()
    {
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

        // Test PL domain link in PL sitemap
        $responsePl = $this->get('http://blog.pl/sitemap.xml');
        $contentPl = $responsePl->getContent();
        $this->assertStringContainsString('http://polski-blog.blog.pl', $contentPl);

        // Test EN domain link in EN sitemap
        $responseEn = $this->get('http://blog.com/sitemap.xml');
        $contentEn = $responseEn->getContent();
        $this->assertStringContainsString('http://english-blog.blog.com', $contentEn);
    }

    public function test_sitemap_has_correct_headers_and_no_css_selectors()
    {
        $response = $this->get('http://blog.pl/sitemap.xml');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $content = $response->getContent();
        $this->assertStringNotContainsString('.common li', $content);
        $this->assertStringNotContainsString('.common span', $content);
        $this->assertStringNotContainsString('.common a', $content);
        $this->assertStringNotContainsString('.common input', $content);
    }

    public function test_it_deletes_physical_sitemap_and_robots_files()
    {
        $sitemapPath = public_path('sitemap.xml');
        $robotsPath = public_path('robots.txt');

        File::put($sitemapPath, 'dummy content');
        File::put($robotsPath, 'dummy content');

        $this->assertTrue(File::exists($sitemapPath));
        $this->assertTrue(File::exists($robotsPath));

        // Trigger sitemap generation which should delete the physical file
        $this->get('http://blog.pl/sitemap.xml');

        $this->assertFalse(File::exists($sitemapPath));

        // Trigger robots generation which should delete the physical file
        $this->get('http://blog.pl/robots.txt');
        $this->assertFalse(File::exists($robotsPath));
    }
}
