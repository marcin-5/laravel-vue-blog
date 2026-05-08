<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class EnneagramTestController extends Controller
{
    public function index(Request $request)
    {
        $locale = 'pl'; // For now, hardcode or detect
        $questionsPath = resource_path("data/enneagram/{$locale}/questions.json");
        $configPath = resource_path('data/enneagram/config.json');

        if (!File::exists($questionsPath) || !File::exists($configPath)) {
            abort(404);
        }

        $questions = json_decode(File::get($questionsPath), true);
        $testConfig = json_decode(File::get($configPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            abort(500, 'Error decoding enneagram data: ' . json_last_error_msg());
        }

        return Inertia::render('EnneagramTest/Index', [
            'testData' => [
                'questions' => $questions,
                'testConfig' => $testConfig['testConfig'],
            ],
            'appDebug' => (bool) config('enneagram.debug'),
        ]);
    }
}
