<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndexNowSubmission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LogsController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'string', Rule::in(['indexnow'])],
            'per_page' => ['sometimes', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 25);

        $logs = IndexNowSubmission::query()
            ->with(['blog:id,name', 'post:id,title'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('app/admin/Logs', [
            'logs' => $logs,
            'type' => $validated['type'] ?? 'indexnow',
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'logTypes' => [
                ['value' => 'indexnow', 'label' => 'IndexNow'],
            ],
        ]);
    }
}
