import { computed, type ComputedRef, ref, type Ref } from 'vue';
import type { FlatOption, PartConfig, SelectedAnswer } from './types';
import { useAnswerSelection } from './useAnswerSelection';
import { useHistory } from './useHistory';

export interface BaseStageState {
    currentPart: Ref<number>;
    skips: Ref<number>;
    shuffledPerQuestion: Ref<Record<string, FlatOption[]>>;
    currentConfig: ComputedRef<PartConfig>;
}

export interface BaseStageProps<TSnapshot> {
    createSnapshot: () => TSnapshot;
    restoreSnapshot: (snapshot: TSnapshot) => void;
    onConfirm: (answers: SelectedAnswer[]) => void;
    onAdvance: (isAnswer: boolean) => void;
    maxAnswersOverride?: ComputedRef<number>;
    autoConfirmDelayMs?: ComputedRef<number>;
    enableAutoConfirmSingle?: Ref<boolean>;
}

export function useBaseEnneagramStage<TSnapshot>(
    getPartConfig: (part: number) => PartConfig,
    factory: (state: BaseStageState) => BaseStageProps<TSnapshot>,
) {
    const currentPart = ref(1);
    const skips = ref(0);
    const shuffledPerQuestion = ref<Record<string, FlatOption[]>>({});

    const currentConfig = computed(() => getPartConfig(currentPart.value));

    const props = factory({
        currentPart,
        skips,
        shuffledPerQuestion,
        currentConfig,
    });

    const {
        selectedAnswers,
        isSelected,
        toggleAnswer,
        clear: clearSelection,
    } = useAnswerSelection({
        maxAnswers: props.maxAnswersOverride ?? computed(() => currentConfig.value.answersPerQuestion),
        onAutoConfirm: () => confirmAnswers(),
        enableAutoConfirmSingle: props.enableAutoConfirmSingle,
        autoConfirmDelayMs: props.autoConfirmDelayMs,
    });

    const { history, recordAnswer, recordSkip, pop: popHistory } = useHistory<TSnapshot>(props.createSnapshot, props.restoreSnapshot);

    const hasReachedMaxSkips = computed(() => currentConfig.value.maxSkips !== undefined && skips.value >= currentConfig.value.maxSkips);

    const canSkip = computed(() => selectedAnswers.value.length === 0 && !hasReachedMaxSkips.value);

    function confirmAnswers() {
        if (selectedAnswers.value.length === 0 && !canSkip.value) {
            return;
        }

        recordAnswer(currentPart.value, selectedAnswers.value, skips.value);
        props.onConfirm(selectedAnswers.value);
        clearSelection();
        props.onAdvance(true);
    }

    function handleSkip() {
        if (hasReachedMaxSkips.value) {
            return;
        }

        recordSkip(currentPart.value, skips.value);
        skips.value++;
        props.onAdvance(false);
    }

    function goBack() {
        const last = popHistory();

        if (!last) {
            return;
        }

        skips.value = last.skipsAtThisPoint;
        selectedAnswers.value = last.type === 'answer' ? [...last.answers] : [];
    }

    return {
        currentPart,
        skips,
        shuffledPerQuestion,
        currentConfig,
        selectedAnswers,
        isSelected,
        toggleAnswer,
        clearSelection,
        history,
        canSkip,
        confirmAnswers,
        handleSkip,
        goBack,
    };
}
