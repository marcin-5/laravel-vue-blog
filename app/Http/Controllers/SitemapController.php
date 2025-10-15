<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function __construct(protected SitemapService $sitemapService)
    {
    }

    public function generate(Request $request)
    {
        $sitemapPath = public_path('sitemap.xml');

        // TTL in seconds (default 1 hour); override via SITEMAP_TTL in .env if needed
        $ttlSeconds = config('sitemap.ttl', 3600);
        $nowTs = now()->timestamp;

        $exists = file_exists($sitemapPath);
        $beforeMtime = null;
        $beforeHash = null;

        if ($exists) {
            $mtime = @filemtime($sitemapPath);
            $beforeMtime = $mtime !== false ? $mtime : null;
            $beforeHash = @sha1_file($sitemapPath);
            $beforeHash = $beforeHash !== false ? $beforeHash : null;
        }

        // Consider file stale if missing or older than TTL
        $stale = !$exists || ($beforeMtime !== null && ($beforeMtime + $ttlSeconds) < $nowTs);

        // Fast path: if not stale and client sent validators, return 304 when appropriate
        if (!$stale && $exists && $beforeMtime !== null) {
            $lastModifiedHeader = gmdate('D, d M Y H:i:s', $beforeMtime) . ' GMT';
            $etag = $beforeHash ? '"' . $beforeHash . '"' : null;

            $ifModifiedSince = $request->headers->get('If-Modified-Since');
            $ifNoneMatch = $request->headers->get('If-None-Match');

            $matchesEtag = $etag && $ifNoneMatch && trim($ifNoneMatch) === $etag;
            $modifiedSinceTs = $ifModifiedSince ? strtotime($ifModifiedSince) : false;
            $notModifiedSince = $modifiedSinceTs !== false && $modifiedSinceTs >= $beforeMtime;

            if ($matchesEtag || $notModifiedSince) {
                return response('', 304)->withHeaders(array_filter([
                    'Content-Type' => 'application/xml',
                    'Last-Modified' => $lastModifiedHeader,
                    'ETag' => $etag,
                    'Cache-Control' => "public, max-age={$ttlSeconds}, s-maxage={$ttlSeconds}",
                ]));
            }
        }

        $regenerated = false;

        if ($stale) {
            $this->sitemapService->generate();
            clearstatcache(true, $sitemapPath);
            $regenerated = true;
        }

        // Check if file exists after generation attempt
        if (!file_exists($sitemapPath)) {
            abort(500, 'Sitemap generation failed');
        }

        $mtime = @filemtime($sitemapPath);
        $afterMtime = $mtime !== false ? $mtime : now()->timestamp;
        $hash = @sha1_file($sitemapPath);
        $afterHash = $hash !== false ? $hash : null;

        $changed = ($beforeHash !== null && $afterHash !== null) ? ($beforeHash !== $afterHash) : $regenerated;

        $lastModifiedHeader = gmdate('D, d M Y H:i:s', $afterMtime) . ' GMT';
        $etag = $afterHash ? '"' . $afterHash . '"' : null;

        return response()->file($sitemapPath, [
            'Content-Type' => 'application/xml',
            'Last-Modified' => $lastModifiedHeader,
            'ETag' => $etag,
            'Cache-Control' => "public, max-age={$ttlSeconds}, s-maxage={$ttlSeconds}",
            // Diagnostics for visibility in responses (optional)
            'X-Sitemap-Regenerated' => $regenerated ? '1' : '0',
            'X-Sitemap-Changed' => $changed ? '1' : '0',
        ]);
    }
}
