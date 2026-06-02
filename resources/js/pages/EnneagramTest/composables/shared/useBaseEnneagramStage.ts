import { computed, ref, type Ref, type ComputedRef } from 'vue';
import type { PartConfig, FlatOption, SelectedAnswer } from './types';
import { useAnswerSelection } from './useAnswerSelection';
import { useHistory } from './useHistory';

export interface BaseStageState {
    currentPart: Ref<number>;
    skips: Ref<number>;
    shuffledPerQuestion: Ref<Record<string, FlatOption[]>>;
    currentConfig: ComputedRef<PartConfig>;
}

export interface BaseStageProps<TSnapshot> {
    getPartConfig: (part: number) => PartConfig;
    createSnapshot: () => TSnapshot;
    restoreSnapshot: (snapshot: TSnapshot) => void;
    onConfirm: (answers: SelectedAnswer[]) => void;
    onAdvance: (isAnswer: boolean) => void;
    maxAnswersOverride?: ComputedRef<number>;
    autoConfirmDelayMs?: ComputedRef<number>;
    enableAutoConfirmSingle?: Ref<boolean>;
}

export function useBaseEnneagramStage<TSnapshot>(factory: (state: BaseStageState) => BaseStageProps<TSnapshot>) {
    const currentPart = ref(1);
    const skips = ref(0);
    const shuffledPerQuestion = ref<Record<string, FlatOption[]>>({});

    // We need to pass a partial state to the factory or just use a proxy-like approach.
    // However, getPartConfig is needed for currentConfig.
    // Let's first create a minimal state and then initialize props.

    // To avoid chicken-and-egg problem, we need to know getPartConfig before currentConfig.
    // But getPartConfig is in props.
    
    // Actually, we can just define a dummy currentConfig and update it, 
    // but better to just pass what's needed.
    
    // Revised approach: factory takes the refs, and we build props from them.
    const stateRefs = {
        currentPart,
        skips,
        shuffledPerQuestion,
    };

    // We need props to get getPartConfig for currentConfig.
    // So we call factory first, but it needs currentConfig... 
    // This is still tricky if currentConfig is in state.
    
    // Let's change BaseStageState to NOT include currentConfig if it's derived.
    // Or just pass a function to get it.
    
    const props = factory({
        ...stateRefs,
        get currentConfig() {
            return currentConfig;
        }
    } as BaseStageState);

    const currentConfig = computed(() => props.getPartConfig(currentPart.value));

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

    const {
        history,
        recordAnswer,
        recordSkip,
        pop: popHistory,
    } = useHistory<TSnapshot>(props.createSnapshot, props.restoreSnapshot);

    const canSkip = computed(() => selectedAnswers.value.length === 0 && (currentConfig.value.maxSkips === undefined || skips.value < currentConfig.value.maxSkips));

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
        if (currentConfig.value.maxSkips !== undefined && skips.value >= currentConfig.value.maxSkips) {
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
