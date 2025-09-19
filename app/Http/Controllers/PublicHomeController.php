<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicHomeController extends Controller
{
    /**
     * Show the welcome page.
     */
    public function welcome(): Response
    {
        return Inertia::render('Welcome', [
            'locale' => app()->getLocale(),
        ]);
    }
}
