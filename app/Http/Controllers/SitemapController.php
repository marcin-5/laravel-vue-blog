<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SitemapController extends Controller
{
    public function __construct(protected SitemapService $sitemapService)
    {
    }

    public function generate(Request $request)
    {
        // Ensure no physical file blocks dynamic generation.
        // Note: If the file exists, this controller might not be hit depending on web server config.
        $this->ensureNoPhysicalSitemap($request);

        $locale = app()->getLocale();
        $cacheKey = "sitemap_{$locale}";
        $ttl = config('sitemap.ttl', 3600);

        $data = Cache::remember($cacheKey, $ttl, function () use ($locale) {
            return [
                'xml' => $this->sitemapService->getSitemap($locale),
                'updated_at' => now()->timestamp,
            ];
        });

        $xml = trim($data['xml']);
        $mtime = $data['updated_at'];
        $etag = '"' . md5($xml) . '"';

        $response = response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s', $mtime) . ' GMT')
            ->header('ETag', $etag)
            ->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}")
            ->header('X-Content-Type-Options', 'nosniff');

        if ($request->isMethodSafe() && ($request->header('If-None-Match') === $etag || (strtotime($request->header('If-Modified-Since') ?? '') >= $mtime))) {
            $response->setStatusCode(304);
        }

        return $response;
    }

    protected function ensureNoPhysicalSitemap(Request $request): void
    {
        $filesToDelete = [
            public_path('sitemap.xml'),
            public_path('robots.txt'),
            public_path('sitemap-' . app()->getLocale() . '.xml'),
        ];

        foreach ($filesToDelete as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }
}
