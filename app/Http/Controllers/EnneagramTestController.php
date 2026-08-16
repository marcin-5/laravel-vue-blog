<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Enneagram\EnneagramTestDataLoader;
use Inertia\Inertia;
use Inertia\Response;

final class EnneagramTestController extends Controller
{
    public function index(EnneagramTestDataLoader $dataLoader): Response
    {
        $data = $dataLoader->load();
        $locale = $data['locale'];

        return Inertia::render('EnneagramTest/Index', [
            'testData' => [
                'questions' => $data['questions'],
                'testConfig' => $data['config'],
            ],
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
