<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __construct(protected SitemapService $sitemapService)
    {
    }

    public function generate(Request $request)
    {
        $locale = app()->getLocale();
        $cacheKey = "sitemap_{$locale}";
        $ttl = config('sitemap.ttl', 3600);

        $data = Cache::remember($cacheKey, $ttl, function () use ($locale) {
            return [
                'xml' => $this->sitemapService->getSitemap($locale),
                'updated_at' => now()->timestamp,
            ];
        });

        $xml = $data['xml'];
        $mtime = $data['updated_at'];
        $etag = '"' . md5($xml) . '"';

        $response = response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s', $mtime) . ' GMT')
            ->header('ETag', $etag)
            ->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}");

        if ($request->isMethodSafe() && ($request->header('If-None-Match') === $etag || (strtotime($request->header('If-Modified-Since') ?? '') >= $mtime))) {
            $response->setStatusCode(304);
        }

        return $response;
    }
}
