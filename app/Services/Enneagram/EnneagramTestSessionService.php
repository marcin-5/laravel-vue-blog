<?php

declare(strict_types=1);

namespace App\Services\Enneagram;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;
use RuntimeException;

final class EnneagramTestSessionService
{
    public function __construct(
        private readonly EnneagramTestDataLoader $dataLoader,
        private readonly EnneagramTestEngine $engine,
        private readonly Session $session,
    ) {
    }

    /**
     * @return array{testId: string, state: array<string, mixed>}
     */
    public function start(bool $extended = false, ?string $locale = null): array
    {
        $data = $this->dataLoader->load($locale);
        $state = $this->engine->start($data, $extended, null, $data['locale']);
        $testId = Str::random(40);
        $sessions = $this->sessions();
        $sessions[$testId] = $state;

        $this->session->put($this->sessionKey(), $sessions);

        return [
            'testId' => $testId,
            'state' => $this->engine->present($state),
        ];
    }

    /**
     * @param list<array<string, mixed>|string> $answers
     * @return array{testId: string, state: array<string, mixed>}
     */
    public function apply(string $testId, string $action, array $answers = []): array
    {
        $state = $this->get($testId);
        $state = $this->engine->apply($state, $action, $answers);
        $sessions = $this->sessions();
        $sessions[$testId] = $state;

        $this->session->put($this->sessionKey(), $sessions);

        return [
            'testId' => $testId,
            'state' => $this->engine->present($state),
        ];
    }

    public function reset(string $testId): void
    {
        $sessions = $this->sessions();

        if (!array_key_exists($testId, $sessions)) {
            throw new RuntimeException('Enneagram test session not found.');
        }

        unset($sessions[$testId]);
        $this->session->put($this->sessionKey(), $sessions);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sessions(): array
    {
        $sessions = $this->session->get($this->sessionKey(), []);

        if (!is_array($sessions)) {
            throw new RuntimeException('Enneagram test session storage is invalid.');
        }

        return $sessions;
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $testId): array
    {
        $state = $this->sessions()[$testId] ?? null;

        if (!is_array($state)) {
            throw new RuntimeException('Enneagram test session not found.');
        }

        return $state;
    }

    private function sessionKey(): string
    {
        return (string) config('enneagram.session.key', 'enneagram.test_sessions');
    }
}
