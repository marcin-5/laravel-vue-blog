<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Enneagram\ActionEnneagramTestRequest;
use App\Http\Requests\Enneagram\ResetEnneagramTestRequest;
use App\Http\Requests\Enneagram\StartEnneagramTestRequest;
use App\Services\Enneagram\EnneagramTestSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class EnneagramTestApiController extends Controller
{
    public function __construct(
        private readonly EnneagramTestSessionService $sessions,
    ) {
    }

    public function start(StartEnneagramTestRequest $request): JsonResponse
    {
        try {
            return $this->stateResponse($this->sessions->start(
                $request->boolean('extended'),
                app()->getLocale(),
            ));
        } catch (RuntimeException) {
            return response()->json([
                'message' => 'Enneagram test data is unavailable.',
            ], 500);
        }
    }

    public function action(ActionEnneagramTestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            return $this->stateResponse($this->sessions->apply(
                (string) $validated['testId'],
                (string) $validated['action'],
                $validated['answers'] ?? [],
            ));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'answers' => [$exception->getMessage()],
            ]);
        } catch (RuntimeException $exception) {
            return $this->sessionConflictResponse($exception);
        }
    }

    public function reset(ResetEnneagramTestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->sessions->reset((string) $validated['testId']);
        } catch (RuntimeException $exception) {
            return $this->sessionConflictResponse($exception);
        }

        return response()->json([
            'contractVersion' => (string) config('enneagram.contract_version', '1'),
            'testId' => (string) $validated['testId'],
            'status' => 'reset',
        ]);
    }

    /**
     * @param array{testId: string, state: array<string, mixed>} $session
     */
    private function stateResponse(array $session): JsonResponse
    {
        return response()->json([
            'contractVersion' => (string) config('enneagram.contract_version', '1'),
            'testId' => $session['testId'],
            'state' => $session['state'],
        ]);
    }

    private function sessionConflictResponse(RuntimeException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'errors' => [
                'testId' => [$exception->getMessage()],
            ],
        ], 409);
    }
}
