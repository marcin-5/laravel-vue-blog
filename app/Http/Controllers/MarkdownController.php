<?php

namespace App\Http\Controllers;

use App\Services\MarkdownService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkdownController extends AuthenticatedController
{
    public function __construct(
        private readonly MarkdownService $markdownService,
    ) {
        parent::__construct();
    }

    /**
     * Preview markdown content.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $preview = $this->markdownService->preview($validated['content']);

        return response()->json($preview);
    }
}
