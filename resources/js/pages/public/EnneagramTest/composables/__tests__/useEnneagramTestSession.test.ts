import { useHttp } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';
import type { EnneagramTestState, SelectedAnswer, TestApiResponse } from '../../types';
import { useEnneagramTestSession } from '../useEnneagramTestSession';

vi.mock('@inertiajs/vue3', () => ({
    useHttp: vi.fn(),
}));

interface MockOptions<TResponse> {
    onSuccess?: (response: TResponse) => void;
    onError?: (errors: Record<string, string[]>) => void;
    onHttpException?: (exception: { status: number }) => void;
    onNetworkError?: () => void;
}

interface MockHttp<TPayload, TResponse> {
    payload: TPayload;
    processing: boolean;
    post: ReturnType<typeof vi.fn<(url: string, options: MockOptions<TResponse>) => Promise<TResponse>>>;
}

const state: EnneagramTestState = {
    version: '1',
    locale: 'pl',
    status: 'in_progress',
    stage: 1,
    part: 1,
    question: { id: 'di-01', question: 'Question' },
    options: [{ key: 'sp', value: 'Option', category: 'sp' }],
    selected_answers: [],
    answer_limit: 1,
    skip_count: 0,
    skip_limit: 1,
    progress: { current: 1, total: 10, answered: 0, maximum: 10 },
    allowed_actions: { answer: true, skip: true, back: false },
    result: null,
};

function response(testId = 'a'.repeat(40)): TestApiResponse {
    return { contractVersion: '1', testId, state };
}

function createHttp<TPayload extends object, TResponse>(payload: TPayload): MockHttp<TPayload, TResponse> {
    return reactive({
        payload,
        processing: false,
        post: vi.fn(),
    }) as MockHttp<TPayload, TResponse>;
}

describe('useEnneagramTestSession', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts a session and stores the server state', async () => {
        const startHttp = createHttp({ extended: false });
        const actionHttp = createHttp({ testId: '', action: 'answer', answers: [] });
        const resetHttp = createHttp({ testId: '' });
        vi.mocked(useHttp)
            .mockReturnValueOnce(startHttp as never)
            .mockReturnValueOnce(actionHttp as never)
            .mockReturnValueOnce(resetHttp as never);
        startHttp.post.mockImplementation(async (_url, options) => {
            options.onSuccess?.(response());
            return response();
        });

        const session = useEnneagramTestSession();
        await session.start(false);

        expect(startHttp.post).toHaveBeenCalledWith('/start', expect.any(Object));
        expect(session.testId.value).toHaveLength(40);
        expect(session.state.value?.question?.id).toBe('di-01');
    });

    it('ignores a second action while a request is processing', async () => {
        const startHttp = createHttp({ extended: false });
        const actionHttp = createHttp({ testId: '', action: 'answer', answers: [] });
        const resetHttp = createHttp({ testId: '' });
        vi.mocked(useHttp)
            .mockReturnValueOnce(startHttp as never)
            .mockReturnValueOnce(actionHttp as never)
            .mockReturnValueOnce(resetHttp as never);
        startHttp.post.mockImplementation(async (_url, options) => {
            options.onSuccess?.(response());
            return response();
        });
        actionHttp.post.mockImplementation(async (_url, options) => {
            actionHttp.processing = true;
            await nextTick();
            options.onSuccess?.(response());
            actionHttp.processing = false;
            return response();
        });

        const session = useEnneagramTestSession();
        await session.start();
        const answer = { key: 'sp', value: 'Option', category: 'sp' };
        const first = session.apply('answer', [answer]);
        const second = session.apply('answer', [answer]);
        await Promise.all([first, second]);

        expect(actionHttp.post).toHaveBeenCalledTimes(1);
    });

    it('sends only the fields accepted by the answer API', async () => {
        const startHttp = createHttp({ extended: false });
        const actionHttp = createHttp({ testId: '', action: 'answer', answers: [] });
        const resetHttp = createHttp({ testId: '' });
        vi.mocked(useHttp)
            .mockReturnValueOnce(startHttp as never)
            .mockReturnValueOnce(actionHttp as never)
            .mockReturnValueOnce(resetHttp as never);
        startHttp.post.mockImplementation(async (_url, options) => {
            options.onSuccess?.(response());
            return response();
        });

        const session = useEnneagramTestSession();
        await session.start();

        const answer = { key: 'sp', value: 'Option', category: 'sp', score: 999 } as SelectedAnswer;
        await session.apply('answer', [answer]);

        expect((actionHttp as typeof actionHttp & { answers: SelectedAnswer[] }).answers).toEqual([{ key: 'sp', value: 'Option', category: 'sp' }]);
    });

    it('exposes validation failures and retries the last operation', async () => {
        const startHttp = createHttp({ extended: false });
        const actionHttp = createHttp({ testId: '', action: 'answer', answers: [] });
        const resetHttp = createHttp({ testId: '' });
        vi.mocked(useHttp)
            .mockReturnValueOnce(startHttp as never)
            .mockReturnValueOnce(actionHttp as never)
            .mockReturnValueOnce(resetHttp as never);
        startHttp.post
            .mockImplementationOnce(async (_url, options) => {
                options.onError?.({ extended: ['The extended value is invalid.'] });
                return response();
            })
            .mockImplementationOnce(async (_url, options) => {
                options.onSuccess?.(response());
                return response();
            });

        const session = useEnneagramTestSession();
        await session.start(true);
        expect(session.error.value?.status).toBe(422);
        expect(session.error.value?.message).toContain('invalid');

        await session.retry();
        expect(startHttp.post).toHaveBeenCalledTimes(2);
        expect(session.error.value).toBeNull();
    });
});
