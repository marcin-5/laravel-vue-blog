<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Enneagram\EnneagramDebugAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EnneagramTestController extends Controller
{
    public function __construct(
        private readonly EnneagramDebugAccessService $debugAccess,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->debugAccess->authorize($request);
        $locale = app()->getLocale();

        return Inertia::render('public/EnneagramTest/Index', [
            'initialLocale' => $locale,
            'debugHints' => $this->debugAccess->hintsEnabled(),
            'debugScores' => $this->debugAccess->scoresEnabled(),
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
