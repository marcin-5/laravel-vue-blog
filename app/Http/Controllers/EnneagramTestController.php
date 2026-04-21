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
        $path = resource_path("data/enneagram/{$locale}/questions.json");

        if (!File::exists($path)) {
            abort(404);
        }

        $data = json_decode(File::get($path), true);

        return Inertia::render('EnneagramTest/Index', [
            'testData' => $data,
        ]);
    }
}
