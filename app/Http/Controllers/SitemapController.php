<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;

class SitemapController extends Controller
{
    public function __construct(protected SitemapService $sitemapService)
    {
    }

    public function generate()
    {
        $sitemapPath = public_path('sitemap.xml');

        // Generate if doesn't exist or is older than 1 hour
        if (!file_exists($sitemapPath) || filemtime($sitemapPath) < now()->subHour()->timestamp) {
            $this->sitemapService->generate();
        }

        return response()->file($sitemapPath, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
