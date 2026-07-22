<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Services\Infrastructure\FileManagementService;
use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __construct(
        protected SitemapService $sitemapService,
        private readonly FileManagementService $fileService,
    ) {}

    public function generate(Request $request)
    {
        $this->ensureDynamicGenerationIsNotBlocked();

        $blog = $this->resolveBlogFromHost($request);
        $locale = $this->resolveLocale($blog);
        $cacheKey = $this->getCacheKey($blog, $locale);
        $ttl = config('sitemap.ttl', 3600);

        $data = Cache::remember($cacheKey, $ttl, function () use ($locale, $blog) {
            return [
                'xml' => $this->sitemapService->getSitemap($locale, $blog),
                'updated_at' => now()->timestamp,
            ];
        });

        $etag = $this->buildEtag($data['xml']);
        $response = $this->buildResponse($data['xml'], $data['updated_at'], $etag, $ttl);

        if ($this->shouldReturnNotModified($request, $etag, $data['updated_at'])) {
            $response->setStatusCode(304);
        }

        return $response;
    }

    protected function ensureDynamicGenerationIsNotBlocked(): void
    {
        $this->fileService->deletePublicFile('sitemap.xml');
        $this->fileService->deletePublicFile('sitemap-' . app()->getLocale() . '.xml');
    }

    protected function resolveBlogFromHost(Request $request): ?Blog
    {
        return Blog::fromHost($request->getHost());
    }

    protected function resolveLocale(?Blog $blog): string
    {
        return $blog ? ($blog->locale ?? app()->getLocale()) : app()->getLocale();
    }

    protected function getCacheKey(?Blog $blog, string $locale): string
    {
        return $blog ? "sitemap_blog_$blog->id" : "sitemap_main_$locale";
    }

    protected function buildEtag(string $xml): string
    {
        return '"' . md5($xml) . '"';
    }

    protected function buildResponse(
        string $xml,
        int $updatedTimestamp,
        string $etag,
        int $ttl,
    ): Response {
        $xml = trim($xml);
        $mtime = $updatedTimestamp;

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s', $mtime) . ' GMT')
            ->header('ETag', $etag)
            ->header('Cache-Control', "public, max-age=$ttl, s-maxage=$ttl")
            ->header('X-Content-Type-Options', 'nosniff');
    }

    protected function shouldReturnNotModified(Request $request, string $etag, int $mtime): bool
    {
        if (!$request->isMethodSafe()) {
            return false;
        }

        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch === $etag) {
            return true;
        }

        $ifModifiedSince = $request->header('If-Modified-Since');
        return $ifModifiedSince && strtotime($ifModifiedSince) >= $mtime;
    }
}
