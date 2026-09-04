<?php

use Illuminate\Support\Facades\File;

test('production web server always delegates dynamic system files to Laravel', function () {
    $caddyfile = File::get(base_path('docker/Caddyfile'));

    expect($caddyfile)
        ->toContain('@dynamicSystemFiles path /sitemap.xml /robots.txt')
        ->toContain('php_fastcgi @dynamicSystemFiles app:9000')
        ->toContain('try_files /index.php');
});

test('production web server applies security headers to every site', function () {
    $caddyfile = File::get(base_path('docker/Caddyfile'));

    expect($caddyfile)
        ->toContain('(security_headers) {')
        ->toContain('Strict-Transport-Security "max-age=31536000; includeSubDomains"')
        ->toContain('X-Content-Type-Options "nosniff"')
        ->toContain('X-Frame-Options "DENY"')
        ->toContain('Referrer-Policy "strict-origin-when-cross-origin"')
        ->toContain('Permissions-Policy "camera=(), geolocation=(), microphone=(), payment=(), usb=()"')
        ->toContain('Cross-Origin-Opener-Policy "same-origin"')
        ->toContain('Cross-Origin-Resource-Policy "same-origin"');

    expect(substr_count($caddyfile, 'import security_headers'))->toBe(2);
});

test('production entrypoint removes persisted dynamic system files', function () {
    $entrypoint = File::get(base_path('docker/scripts/entrypoint.sh'));

    expect($entrypoint)
        ->toContain('/var/www/html/public/sitemap.xml')
        ->toContain('/var/www/html/public/robots.txt')
        ->toContain('/var/www/html/public/sitemap-*.xml');
});
