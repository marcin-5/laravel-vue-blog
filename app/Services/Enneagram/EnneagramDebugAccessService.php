<?php

declare(strict_types=1);

namespace App\Services\Enneagram;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

final class EnneagramDebugAccessService
{
    private const string SESSION_KEY = 'enneagram.debug_access';

    private const string HINTS_QUERY_PARAMETER = 'debug_hints';

    private const string SCORES_QUERY_PARAMETER = 'debug_scores';

    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function authorize(Request $request): void
    {
        $debugEnabled = (bool) config('enneagram.debug', false);

        $this->session->put(self::SESSION_KEY, [
            'hints' => $debugEnabled && $this->matchesConfiguredKey(
                $request,
                'debug_hints_key',
                self::HINTS_QUERY_PARAMETER,
            ),
            'scores' => $debugEnabled && $this->matchesConfiguredKey(
                $request,
                'debug_scores_key',
                self::SCORES_QUERY_PARAMETER,
            ),
        ]);
    }

    public function hintsEnabled(): bool
    {
        return $this->isEnabled('hints');
    }

    public function scoresEnabled(): bool
    {
        return $this->isEnabled('scores');
    }

    private function isEnabled(string $capability): bool
    {
        if (!(bool) config('enneagram.debug', false)) {
            return false;
        }

        $access = $this->session->get(self::SESSION_KEY, []);

        return is_array($access) && ($access[$capability] ?? false) === true;
    }

    private function matchesConfiguredKey(Request $request, string $configKey, string $queryParameter): bool
    {
        $configuredKey = (string) config('enneagram.' . $configKey, '');
        $providedKey = $request->query($queryParameter);

        return $configuredKey !== ''
            && is_string($providedKey)
            && hash_equals($configuredKey, $providedKey);
    }
}
