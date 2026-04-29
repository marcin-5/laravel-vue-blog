<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class EnneagramTestController extends Controller
{
    /**
     * Whether answers loaded from questions.json should be randomized.
     * When enabled, a single shuffled copy is kept in session to ensure
     * consistent order while the user navigates back/next within the test.
     */
    private const SHUFFLE_ANSWERS = true;

    /**
     * Whether questions order should be randomized using priority weighting.
     * Priority rule: 0 => 1 copy in the pool, 1 => 2 copies, 2 => 3 copies.
     * After shuffling the pool, duplicates are removed keeping the first
     * occurrence, which biases higher priorities toward the front.
     */
    private const SHUFFLE_QUESTIONS = true;

    public function index(Request $request)
    {
        $locale = 'pl'; // For now, hardcode or detect
        $path = resource_path("data/enneagram/{$locale}/questions.json");

        if (!File::exists($path)) {
            abort(404);
        }

        $data = json_decode(File::get($path), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            abort(500, 'Error decoding questions.json: ' . json_last_error_msg());
        }

        // Randomize questions (by priority) and answers on each fresh visit
        // so every reopening of the test gets a new draw. While the user
        // navigates within the SPA (wstecz/dalej), frontend state preserves
        // order; we don't need session persistence here.
        if (self::SHUFFLE_QUESTIONS) {
            $data = $this->shuffleQuestionsByPriority($data);
        }
        if (self::SHUFFLE_ANSWERS) {
            $data = $this->shuffleAnswersDeep($data);
        }

        return Inertia::render('EnneagramTest/Index', [
            'testData' => $data,
            'appDebug' => (bool) config('app.debug'),
        ]);
    }

    /**
     * Reorder questions using priority-weighted shuffling.
     * Priority: 0 => 1 copy, 1 => 2 copies, 2 => 3 copies.
     * Build a pool with repeated IDs, shuffle, then keep first unique IDs
     * to define the final order.
     *
     * @param  array  $data
     * @return array
     */
    private function shuffleQuestionsByPriority(array $data): array
    {
        if (!isset($data['questions']) || !is_array($data['questions'])) {
            return $data;
        }

        $questions = $data['questions'];
        $byId = [];
        $pool = [];

        foreach ($questions as $idx => $q) {
            $id = $q['id'] ?? (string) $idx;
            $byId[$id] = $q;

            $priority = (int) ($q['priority'] ?? 0);
            $copies = 1 + $priority; // priority indicates additional copies
            for ($i = 0; $i < $copies; $i++) {
                $pool[] = $id;
            }
        }

        if (count($pool) > 1) {
            shuffle($pool);
        }

        $seen = [];
        $orderIds = [];
        foreach ($pool as $id) {
            if (!isset($seen[$id])) {
                $seen[$id] = true;
                $orderIds[] = $id;
            }
        }

        $reordered = [];
        foreach ($orderIds as $id) {
            if (isset($byId[$id])) {
                $reordered[] = $byId[$id];
            }
        }

        // Append any questions that somehow didn't make it into $orderIds (safety net)
        if (count($reordered) < count($questions)) {
            foreach ($questions as $q) {
                $id = $q['id'] ?? null;
                if ($id === null) {
                    $reordered[] = $q;
                } elseif (!in_array($id, $orderIds, true)) {
                    $reordered[] = $q;
                }
            }
        }

        $data['questions'] = $reordered;
        return $data;
    }

    /**
     * Shuffle the order of answers within questions while preserving
     * their category keys for scoring. Both the order of categories
     * (object keys) and the order within arrays are randomized.
     *
     * @param  array  $data
     * @return array
     */
    private function shuffleAnswersDeep(array $data): array
    {
        if (!isset($data['questions']) || !is_array($data['questions'])) {
            return $data;
        }

        foreach ($data['questions'] as $i => $question) {
            if (!isset($question['answerLists']) || !is_array($question['answerLists'])) {
                continue;
            }

            // Shuffle the order of category keys (preserve key associations)
            $keys = array_keys($question['answerLists']);
            if (count($keys) > 1) {
                shuffle($keys);
            }

            $shuffledAnswerLists = [];
            foreach ($keys as $key) {
                $value = $question['answerLists'][$key];
                if (is_array($value)) {
                    // Reindex and shuffle inner options if they are a list
                    $inner = array_values($value);
                    if (count($inner) > 1) {
                        shuffle($inner);
                    }
                    $value = $inner;
                }
                $shuffledAnswerLists[$key] = $value;
            }

            $data['questions'][$i]['answerLists'] = $shuffledAnswerLists;
        }

        return $data;
    }
}
