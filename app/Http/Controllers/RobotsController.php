<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;

class RobotsController extends Controller
{
    /**
     * Generate the robots.txt file.
     */
    public function generate(): Response
    {
        $content = "User-agent: *\n";
        $content .= "Disallow: /dashboard\n";
        $content .= "Disallow: /login\n";
        $content .= "Disallow: /register\n";

        if (App::environment('production')) {
            $content .= "Allow: /\n\n";
            $content .= 'Sitemap: ' . URL::to('/sitemap.xml') . "\n";
            $content .= 'Host: ' . URL::to('/') . "\n";
        } else {
            $content .= "Disallow: /\n";
        }

        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
