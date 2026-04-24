import { shallowRef, triggerRef } from 'vue';
import type { BaseHistoryItem, SelectedAnswer } from './types';

/**
 * Generic undo history. Callers decide what to capture via `capture()`
 * and how to restore via `restore()`.
 *
 * Uses `shallowRef` because snapshots are plain data structures —
 * we want reactivity on the array reference (for `history.value.length`
 * in templates), not deep reactivity on every captured field, which
 * would also break generic typing via `UnwrapRef`.
 */
export function useHistory<TSnapshot>(capture: () => TSnapshot, restore: (snapshot: TSnapshot) => void) {
    const history = shallowRef<BaseHistoryItem<TSnapshot>[]>([]);

    function push(entry: Omit<BaseHistoryItem<TSnapshot>, 'snapshot'>) {
        history.value.push({ ...entry, snapshot: capture() });
        triggerRef(history);
    }

    function pop(): BaseHistoryItem<TSnapshot> | null {
        const last = history.value.pop();
        if (!last) return null;
        triggerRef(history);
        restore(last.snapshot);
        return last;
    }

    function recordAnswer(part: number, answers: SelectedAnswer[], skipsAtThisPoint: number) {
        push({ part, type: 'answer', answers: [...answers], skipsAtThisPoint });
    }

    function recordSkip(part: number, skipsAtThisPoint: number) {
        push({ part, type: 'skip', answers: [], skipsAtThisPoint });
    }

    return { history, push, pop, recordAnswer, recordSkip };
}
