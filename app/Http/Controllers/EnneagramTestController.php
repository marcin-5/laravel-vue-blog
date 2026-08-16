<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class EnneagramTestController extends Controller
{
    public function index(): Response
    {
        $locale = app()->getLocale();

        return Inertia::render('public/EnneagramTest/Index', [
            'initialLocale' => $locale,
            'appDebug' => (bool) config('enneagram.debug'),
            'autoConfirmSingleDefault' => (bool) config('enneagram.auto_confirm_single'),
            'seo' => [
                'title' => $locale === 'pl' ? 'Test enneagramu' : 'Enneagram test',
                'description' => $locale === 'pl'
                    ? 'Poznaj swój typ enneagramu.'
                    : 'Discover your enneagram type.',
            ],
        ]);
    }
}
