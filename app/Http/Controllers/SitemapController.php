<?php

namespace App\Http\Controllers;

use App\Services\Infrastructure\FileManagementService;
use App\Services\SitemapService;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __construct(
        protected SitemapService $sitemapService,
        private readonly FileManagementService $fileService,
    ) {}

    public function generate(Request $request)
    {
        // Ensure no physical file blocks dynamic generation.
        $this->ensureNoPhysicalSitemap();

        $host = $request->getHost();
        $blog = Blog::fromHost($host);

        $locale = $blog ? ($blog->locale ?? app()->getLocale()) : app()->getLocale();
        $cacheKey = $blog ? "sitemap_blog_{$blog->id}" : "sitemap_main_$locale";
        $ttl = config('sitemap.ttl', 3600);

        $data = Cache::remember($cacheKey, $ttl, function () use ($locale, $blog) {
            return [
                'xml' => $this->sitemapService->getSitemap($locale, $blog),
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
            ->header('Cache-Control', "public, max-age=$ttl, s-maxage=$ttl")
            ->header('X-Content-Type-Options', 'nosniff');

        if ($request->isMethodSafe() && ($request->header('If-None-Match') === $etag || (strtotime(
            $request->header('If-Modified-Since') ?? '',
        ) >= $mtime))) {
            $response->setStatusCode(304);
        }

        return $response;
    }

    protected function ensureNoPhysicalSitemap(): void
    {
        $this->fileService->deletePublicFile('sitemap.xml');
        $this->fileService->deletePublicFile('robots.txt');
        $this->fileService->deletePublicFile('sitemap-' . app()->getLocale() . '.xml');
    }
}
