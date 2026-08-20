import { useHttp } from '@inertiajs/vue3';
import { computed, shallowReadonly, shallowRef } from 'vue';
import type {
    ActionTestPayload,
    EnneagramTestState,
    ResetTestResponse,
    SelectedAnswer,
    StartTestPayload,
    TestAction,
    TestApiResponse,
} from '../types';

type LastOperation = 'start' | 'action' | null;

interface ApiErrorState {
    message: string;
    status: number | null;
}

const defaultActionPayload = (): ActionTestPayload => ({
    testId: '',
    action: 'answer',
    answers: [],
});

function normalizeAnswers(answers: SelectedAnswer[]): SelectedAnswer[] {
    return answers.map(({ key, value, category }) => ({ key, value, category }));
}

export function useEnneagramTestSession() {
    const state = shallowRef<EnneagramTestState | null>(null);
    const testId = shallowRef<string | null>(null);
    const error = shallowRef<ApiErrorState | null>(null);
    const lastOperation = shallowRef<LastOperation>(null);

    const startHttp = useHttp<StartTestPayload, TestApiResponse>({ extended: false });
    const actionHttp = useHttp<ActionTestPayload, TestApiResponse>(defaultActionPayload());
    const resetHttp = useHttp<{ testId: string }, ResetTestResponse>({ testId: '' });

    const processing = computed(() => startHttp.processing || actionHttp.processing || resetHttp.processing);

    const hasSession = computed(() => state.value !== null && testId.value !== null);

    function setError(message: string, status: number | null = null): void {
        error.value = { message, status };
    }

    function clearError(): void {
        error.value = null;
    }

    function handleValidationError(errors: Record<string, string | string[]>): void {
        const message = Object.values(errors)
            .flatMap((value) => (Array.isArray(value) ? value : [value]))
            .join(' ');

        setError(message || 'The test request could not be validated.', 422);
    }

    function handleHttpException(status: number): void {
        setError(
            status === 409 ? 'This test session is no longer available. Please start again.' : 'The test service returned an unexpected response.',
            status,
        );
    }

    function handleNetworkError(): void {
        setError('The test service could not be reached. Check your connection and retry.');
    }

    function acceptState(response: TestApiResponse): void {
        testId.value = response.testId;
        state.value = response.state;
    }

    async function start(extended = false): Promise<void> {
        if (processing.value) {
            return;
        }

        clearError();
        startHttp.extended = extended;
        lastOperation.value = 'start';

        await startHttp.post('/start', {
            onSuccess: acceptState,
            onError: handleValidationError,
            onHttpException: (exception) => handleHttpException(exception.status ?? 500),
            onNetworkError: handleNetworkError,
        });
    }

    async function apply(action: TestAction, answers: SelectedAnswer[] = []): Promise<void> {
        if (processing.value || !testId.value || !state.value?.allowed_actions[action]) {
            return;
        }

        clearError();
        actionHttp.testId = testId.value;
        actionHttp.action = action;
        actionHttp.answers = normalizeAnswers(answers);
        lastOperation.value = 'action';

        await actionHttp.post('/action', {
            onSuccess: acceptState,
            onError: handleValidationError,
            onHttpException: (exception) => handleHttpException(exception.status ?? 500),
            onNetworkError: handleNetworkError,
        });
    }

    async function reset(): Promise<void> {
        if (processing.value || !testId.value) {
            return;
        }

        clearError();
        resetHttp.testId = testId.value;

        await resetHttp.post('/reset', {
            onSuccess: () => {
                state.value = null;
                testId.value = null;
                lastOperation.value = null;
            },
            onHttpException: (exception) => handleHttpException(exception.status ?? 500),
            onNetworkError: handleNetworkError,
        });
    }

    async function retry(): Promise<void> {
        if (lastOperation.value === 'start') {
            await start(startHttp.extended);
            return;
        }

        if (lastOperation.value === 'action') {
            await apply(actionHttp.action, actionHttp.answers);
        }
    }

    return {
        state: shallowReadonly(state),
        testId: shallowReadonly(testId),
        error: shallowReadonly(error),
        processing,
        hasSession,
        start,
        apply,
        reset,
        retry,
        clearError,
    };
}
