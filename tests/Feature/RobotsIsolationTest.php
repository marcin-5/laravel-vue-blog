<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RobotsIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('app.domain', 'osobliwy.pl');
    }

    public function test_robots_txt_on_main_domain_contains_main_sitemap_and_disallows_groups()
    {
        $originalEnv = App::environment();
        $this->app['env'] = 'production';

        $response = $this->get('http://osobliwy.pl/robots.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *');
        $response->assertSee('Disallow: /_/');
        $response->assertSee('Sitemap: http://osobliwy.pl/sitemap.xml');
        $response->assertSee('Host: osobliwy.pl');

        $this->app['env'] = $originalEnv;
    }

    public function test_robots_txt_on_subdomain_contains_blog_sitemap_and_host()
    {
        $originalEnv = App::environment();
        $this->app['env'] = 'production';

        $user = User::factory()->create();
        $blog = Blog::factory()->create([
            'user_id' => $user->id,
            'slug' => 'my-blog',
            'is_published' => true,
        ]);

        $response = $this->get('http://my-blog.osobliwy.pl/robots.txt');

        $response->assertStatus(200);
        $response->assertSee('User-agent: *');
        $response->assertSee('Disallow: /_/');
        $response->assertSee('Sitemap: http://my-blog.osobliwy.pl/sitemap.xml');
        $response->assertSee('Host: my-blog.osobliwy.pl');

        $this->app['env'] = $originalEnv;
    }

    public function test_robots_txt_disallows_all_in_non_production_on_subdomain()
    {
        $originalEnv = App::environment();
        $this->app['env'] = 'local';

        $user = User::factory()->create();
        Blog::factory()->create([
            'user_id' => $user->id,
            'slug' => 'my-blog',
            'is_published' => true,
        ]);

        $response = $this->get('http://my-blog.osobliwy.pl/robots.txt');

        $response->assertStatus(200);
        $response->assertSee('Disallow: /');
        $response->assertDontSee('Allow: /');

        $this->app['env'] = $originalEnv;
    }
}
