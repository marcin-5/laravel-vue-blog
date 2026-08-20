<?php

use Illuminate\Support\Facades\File;

test('production web server always delegates dynamic system files to Laravel', function () {
    $caddyfile = File::get(base_path('docker/Caddyfile'));

    expect($caddyfile)
        ->toContain('@dynamicSystemFiles path /sitemap.xml /robots.txt')
        ->toContain('php_fastcgi @dynamicSystemFiles app:9000')
        ->toContain('try_files /index.php');
});

test('production entrypoint removes persisted dynamic system files', function () {
    $entrypoint = File::get(base_path('docker/scripts/entrypoint.sh'));

    expect($entrypoint)
        ->toContain('/var/www/html/public/sitemap.xml')
        ->toContain('/var/www/html/public/robots.txt')
        ->toContain('/var/www/html/public/sitemap-*.xml');
});
