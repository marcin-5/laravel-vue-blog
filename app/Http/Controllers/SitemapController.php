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
        $this->sitemapService->generate();

        return response()->json(['message' => 'Sitemap generated successfully']);
    }
}
