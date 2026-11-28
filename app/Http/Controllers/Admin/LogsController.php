<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndexNowSubmission;
use App\Services\IndexNowFailureRetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LogsController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    private const INDEX_NOW_FAILURES_LIMIT = 10;

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
            'indexNowFailureCount' => DB::table('failed_jobs')
                ->where('payload', 'like', '%IndexNowSubmitJob%')
                ->count(),
            'logTypes' => [
                ['value' => 'indexnow', 'label' => 'IndexNow'],
            ],
        ]);
    }

    public function indexNowFailures(): JsonResponse
    {
        $failures = DB::table('failed_jobs')
            ->select(['uuid', 'failed_at', 'exception'])
            ->where('payload', 'like', '%IndexNowSubmitJob%')
            ->orderByDesc('failed_at')
            ->orderByDesc('id')
            ->limit(self::INDEX_NOW_FAILURES_LIMIT)
            ->get();

        return response()->json(['failures' => $failures]);
    }

    public function retryIndexNowFailure(string $uuid, IndexNowFailureRetryService $retryService): JsonResponse
    {
        if (!$retryService->retry($uuid)) {
            abort(404);
        }

        return response()->json(['retried' => true]);
    }
}
