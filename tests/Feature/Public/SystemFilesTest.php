<?php

use App\Services\Infrastructure\FileManagementService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    // Cleanup any files that might have been created
    @unlink(public_path('robots.txt'));
    @unlink(public_path('sitemap.xml'));
});

test('robots.txt returns correct content in production', function () {
    // Force production environment
    $originalEnv = App::environment();
    $this->app['env'] = 'production';

    $response = $this->get('/robots.txt');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('User-agent: *');
    $response->assertSee('Allow: /');
    $response->assertSee('Sitemap: ' . URL::to('/sitemap.xml'));

    // Restore env
    $this->app['env'] = $originalEnv;
});

test('robots.txt disallows all in non-production', function () {
    // Force local environment
    $originalEnv = App::environment();
    $this->app['env'] = 'local';

    $response = $this->get('/robots.txt');

    $response->assertStatus(200);
    $response->assertSee('Disallow: /');
    $response->assertDontSee('Allow: /');

    // Restore env
    $this->app['env'] = $originalEnv;
});

test('robots.txt removes physical file if it exists', function () {
    File::put(public_path('robots.txt'), 'physical file content');
    expect(File::exists(public_path('robots.txt')))->toBeTrue();

    $this->get('/robots.txt');

    expect(File::exists(public_path('robots.txt')))->toBeFalse();
});

test('sitemap.xml returns 200 and removes physical files', function () {
    File::put(public_path('sitemap.xml'), 'physical sitemap');
    File::put(public_path('robots.txt'), 'physical robots');

    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');

    expect(File::exists(public_path('sitemap.xml')))->toBeFalse();
    expect(File::exists(public_path('robots.txt')))->toBeFalse();
});

test('FileManagementService prevents path traversal and unauthorized files', function () {
    $service = new FileManagementService;

    // Valid files
    expect(fn() => $service->deletePublicFile('robots.txt'))->not->toThrow(InvalidArgumentException::class);
    expect(fn() => $service->deletePublicFile('sitemap-pl.xml'))->not->toThrow(InvalidArgumentException::class);

    // Path traversal
    expect(fn() => $service->deletePublicFile('../.env'))->toThrow(
        InvalidArgumentException::class,
        'Invalid filename provided',
    );
    expect(fn() => $service->deletePublicFile('folder/file.txt'))->toThrow(
        InvalidArgumentException::class,
        'Invalid filename provided',
    );

    // Unauthorized files
    expect(fn() => $service->deletePublicFile('index.php'))->toThrow(
        InvalidArgumentException::class,
        'is not on the allowed list',
    );
    expect(fn() => $service->deletePublicFile('config.json'))->toThrow(
        InvalidArgumentException::class,
        'is not on the allowed list',
    );
});
