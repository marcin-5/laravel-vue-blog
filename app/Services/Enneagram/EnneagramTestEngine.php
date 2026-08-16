<?php

declare(strict_types=1);

namespace App\Services\Enneagram;

use InvalidArgumentException;
use Random\RandomException;
use RuntimeException;

final class EnneagramTestEngine
{
    private const string VERSION = '1';

    /** @var list<string> */
    private const array INSTINCTS = ['sp', 'so', 'sx'];

    /** @var list<string> */
    private const array TYPES = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     * @throws RandomException
     */
    public function start(array $data, bool $extended = false, ?int $seed = null, ?string $locale = null): array
    {
        $questions = $data['questions'] ?? null;
        $config = $data['config'] ?? null;

        if (!is_array($questions) || !is_array($config)) {
            throw new InvalidArgumentException('Enneagram engine requires validated questions and configuration.');
        }

        $seed ??= random_int(1, 4_294_967_295);
        $activeConfig = $extended ? $this->buildExtendedConfig($config, $questions) : $config;
        $pools = $this->buildPools($questions, $seed);
        $options = $this->buildOptions($pools, $seed);

        $state = [
            'version' => self::VERSION,
            'locale' => $locale ?? (string) ($data['locale'] ?? app()->getLocale()),
            'extended' => $extended,
            'seed' => $seed,
            'status' => 'in_progress',
            'stage' => 1,
            'part' => 1,
            'question_index' => 0,
            'skips' => 0,
            'config' => $activeConfig,
            'pools' => $pools,
            'options_by_question' => $options,
            'scores' => [
                'stage1' => [
                    'part1' => $this->emptyInstinctScores(),
                    'part2' => $this->emptyInstinctScores(),
                ],
                'stage2' => [
                    'total' => $this->emptyTypeScores(),
                    'per_part' => [
                        1 => $this->emptyTypeScores(),
                        2 => $this->emptyTypeScores(),
                        3 => $this->emptyTypeScores(),
                        4 => $this->emptyTypeScores(),
                    ],
                ],
            ],
            'stage1_answered' => ['part1' => 0, 'part2' => 0],
            'stage1_part1_winner' => null,
            'stage1_extra_asked' => false,
            'stage1_duplicate_answers' => 0,
            'stage2_selected' => ['part1' => [], 'part3' => []],
            'stage2_bonus' => [1 => 0, 2 => 0, 3 => 0, 4 => 0],
            'stage2_pool_indices' => ['sp' => 0, 'so' => 0, 'sx' => 0],
            'result' => ['stage1' => null, 'stage2' => null],
            'selected_answers' => [],
            'history' => [],
        ];

        return $this->decorate($state);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  list<array<string, mixed>>  $questions
     * @return array<string, mixed>
     */
    private function buildExtendedConfig(array $config, array $questions): array
    {
        $extended = $config;

        foreach (['stage1', 'stage2'] as $stage) {
            foreach ($extended['stages'][$stage] as &$part) {
                $part['maxSkips'] = 0;
            }
            unset($part);
        }

        $stage1Part1Pool = $this->countQuestionsWithPrefix($questions, 'di-');
        $stage1Part2Pool = $this->countQuestionsWithPrefix($questions, 'ri-');
        $this->applyExtendedLimit(
            $extended['stages']['stage1']['part1'],
            $config['stages']['stage1']['part1'],
            $stage1Part1Pool,
            0.6,
        );
        $this->applyExtendedLimit(
            $extended['stages']['stage1']['part2'],
            $config['stages']['stage1']['part2'],
            $stage1Part2Pool,
            0.4,
        );

        foreach (['part1', 'part2'] as $part) {
            if (array_key_exists('minLead', $config['stages']['stage1'][$part])) {
                $extended['stages']['stage1'][$part]['minLead'] = $config['stages']['stage1'][$part]['minLead'] + 1;
            }
        }

        if (array_key_exists('minLeadAlternative', $config['stages']['stage1']['part2'])) {
            $extended['stages']['stage1']['part2']['minLeadAlternative'] = $config['stages']['stage1']['part2']['minLeadAlternative'] + 1;
        }

        $poolSize = min(
            $this->countQuestionsWithPrefix($questions, 'sp-'),
            $this->countQuestionsWithPrefix($questions, 'so-'),
            $this->countQuestionsWithPrefix($questions, 'sx-'),
        );
        $this->applyExtendedLimit(
            $extended['stages']['stage2']['part1'],
            $config['stages']['stage2']['part1'],
            $poolSize,
            0.6,
        );
        $this->applyExtendedLimit(
            $extended['stages']['stage2']['part2'],
            $config['stages']['stage2']['part2'],
            $poolSize,
            0.4,
        );

        foreach (['part1', 'part2'] as $part) {
            if (array_key_exists('minLead', $config['stages']['stage2'][$part])) {
                $extended['stages']['stage2'][$part]['minLead'] = $config['stages']['stage2'][$part]['minLead'] + 1;
            }
        }

        return $extended;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    private function countQuestionsWithPrefix(array $questions, string $prefix): int
    {
        return count(array_filter($questions, fn(array $question): bool => str_starts_with($question['id'], $prefix)));
    }

    /**
     * @param  array<string, int|string>  $target
     * @param  array<string, int|string>  $original
     */
    private function applyExtendedLimit(array &$target, array $original, int $poolSize, float $ratio): void
    {
        if ($poolSize > 1) {
            $target['maxQuestions'] = max($original['maxQuestions'], (int) floor(($poolSize - 1) * $ratio));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<string, mixed>
     */
    private function buildPools(array $questions, int &$seed): array
    {
        $stage1Part1 = array_filter($questions, fn(array $question): bool => str_starts_with($question['id'], 'di-'))
                |> array_values(...)
                |> (fn($x) => $this->shuffleByPriority($x, $seed));
        $stage1Part2 = array_filter($questions, fn(array $question): bool => str_starts_with($question['id'], 'ri-'))
                |> array_values(...)
                |> (fn($x) => $this->shuffleByPriority($x, $seed));
        $stage2 = [];

        foreach (self::INSTINCTS as $instinct) {
            $stage2[$instinct] = array_filter(
                    $questions,
                    fn(array $question): bool => str_starts_with($question['id'], "$instinct-"),
                )
                    |> array_values(...)
                    |> (fn($x) => $this->shuffleByPriority($x, $seed));
        }

        return [
            'stage1' => ['part1' => $stage1Part1, 'part2' => $stage1Part2],
            'stage2' => $stage2,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return list<array<string, mixed>>
     */
    private function shuffleByPriority(array $questions, int &$seed): array
    {
        $weighted = [];

        foreach ($questions as $question) {
            for ($index = 0; $index <= (int) ($question['priority'] ?? 0); $index++) {
                $weighted[] = $question['id'];
            }
        }

        $weighted = $this->shuffleValues($weighted, $seed);
        $uniqueIds = array_values(array_unique($weighted));

        $questionsById = [];

        foreach ($questions as $question) {
            $questionsById[$question['id']] = $question;
        }

        return array_map(fn(string $id): ?array => $questionsById[$id] ?? null, $uniqueIds)
                |> array_filter(...)
                |> array_values(...);
    }

    /**
     * @template T
     * @param  list<T>  $values
     * @return list<T>
     */
    private function shuffleValues(array $values, int &$seed): array
    {
        for ($index = count($values) - 1; $index > 0; $index--) {
            $swapIndex = (int) floor($this->nextRandom($seed) * ($index + 1));
            [$values[$index], $values[$swapIndex]] = [$values[$swapIndex], $values[$index]];
        }

        return $values;
    }

    private function nextRandom(int &$seed): float
    {
        $seed = ($seed * 1_664_525 + 1_013_904_223) % 4_294_967_296;

        return $seed / 4_294_967_296;
    }

    /**
     * @param  array<string, mixed>  $pools
     * @return array<string, mixed>
     */
    private function buildOptions(array $pools, int &$seed): array
    {
        $options = [];

        foreach ([$pools['stage1']['part1'], $pools['stage1']['part2'], ...array_values($pools['stage2'])] as $pool) {
            foreach ($pool as $question) {
                $options[$question['id']] ??= $this->shuffleOptions($question['answerLists'], $seed);
            }
        }

        return $options;
    }

    /**
     * @param  array<string, string|list<string>>  $answerLists
     * @return list<array{key: string, value: string, category: string}>
     */
    private function shuffleOptions(array $answerLists, int &$seed): array
    {
        $options = [];

        foreach ($answerLists as $category => $values) {
            if (is_array($values)) {
                foreach ($values as $index => $value) {
                    $options[] = ['key' => "$category-$index", 'value' => $value, 'category' => $category];
                }
            } else {
                $options[] = ['key' => $category, 'value' => $values, 'category' => $category];
            }
        }

        return $this->shuffleValues($options, $seed);
    }

    /** @return array{sp: int, so: int, sx: int} */
    private function emptyInstinctScores(): array
    {
        return ['sp' => 0, 'so' => 0, 'sx' => 0];
    }

    /** @return array<string, int> */
    private function emptyTypeScores(): array
    {
        return array_fill_keys(self::TYPES, 0);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function decorate(array $state): array
    {
        $state['question'] = $this->currentQuestion($state);
        $state['options'] = $this->currentOptions($state);
        $state['progress'] = $this->progress($state);
        $state['allowed_actions'] = [
            'answer' => $state['status'] === 'in_progress',
            'skip' => $state['status'] === 'in_progress' && (int) $state['skips'] < $this->currentConfig(
                    $state,
                )['maxSkips'],
            'back' => $state['history'] !== [],
        ];

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>|null
     */
    private function currentQuestion(array $state): ?array
    {
        if ($state['status'] !== 'in_progress') {
            return null;
        }

        if ((int) $state['stage'] === 1) {
            return $state['pools']['stage1']["part{$state['part']}"][$state['question_index']] ?? null;
        }

        $instinct = $this->stage2Instinct($state, (int) $state['part']);
        $index = $state['stage2_pool_indices'][$instinct];

        return $state['pools']['stage2'][$instinct][$index] ?? null;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function stage2Instinct(array $state, int $part): string
    {
        $stage1 = $state['result']['stage1'];

        if (!is_array($stage1)) {
            throw new RuntimeException('Stage two cannot start without stage one results.');
        }

        return $part <= 2 ? (string) $stage1['dominant'] : (string) $stage1['secondary'];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<array<string, mixed>>
     */
    private function currentOptions(array $state): array
    {
        $question = $this->currentQuestion($state);

        if ($question === null) {
            return [];
        }

        $options = $state['options_by_question'][$question['id']] ?? [];

        if ((int) $state['stage'] === 2 && (int) $state['part'] === 2) {
            $options = array_values(
                array_filter(
                    $options,
                    fn(array $option): bool => isset($state['stage2_selected']['part1'][$option['category']]),
                ),
            );
        }

        if ((int) $state['stage'] === 2 && (int) $state['part'] === 4) {
            $options = array_values(
                array_filter(
                    $options,
                    fn(array $option): bool => isset($state['stage2_selected']['part1'][$option['category']])
                        || isset($state['stage2_selected']['part3'][$option['category']]),
                ),
            );
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{current: int, total: int, answered: int, maximum: int}
     */
    private function progress(array $state): array
    {
        if ($state['status'] !== 'in_progress') {
            return ['current' => 0, 'total' => 0, 'answered' => 0, 'maximum' => 0];
        }

        $question = $this->currentQuestion($state);
        $total = (int) $state['stage'] === 1
            ? count($state['pools']['stage1']["part{$state['part']}"])
            : count($state['pools']['stage2'][$this->stage2Instinct($state, (int) $state['part'])]);

        return [
            'current' => $question === null ? $total : ((int) $state['stage'] === 1 ? $state['question_index'] + 1 : $state['stage2_pool_indices'][$this->stage2Instinct(
                    $state,
                    (int) $state['part'],
                )] + 1),
            'total' => $total,
            'answered' => (int) $state['stage'] === 1
                ? $state['stage1_answered']["part{$state['part']}"]
                : $state['question_index'],
            'maximum' => $this->currentConfig($state)['maxQuestions'],
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, int|string>
     */
    private function currentConfig(array $state): array
    {
        $config = $state['config']['stages']["stage{$state['stage']}"]["part{$state['part']}"] ?? null;

        if (!is_array($config)) {
            throw new RuntimeException('The enneagram state contains an invalid current part.');
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<array<string, mixed>|string>  $answers
     * @return array<string, mixed>
     */
    public function apply(array $state, string $action, array $answers = []): array
    {
        if (($state['status'] ?? null) !== 'in_progress') {
            throw new InvalidArgumentException('The enneagram test is no longer in progress.');
        }

        return match ($action) {
            'answer' => $this->answer($state, $answers),
            'skip' => $this->skip($state, $answers),
            'back' => $this->back($state),
            default => throw new InvalidArgumentException("Unsupported enneagram action [$action]."),
        };
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<array<string, mixed>|string>  $answers
     * @return array<string, mixed>
     */
    private function answer(array $state, array $answers): array
    {
        $options = $this->currentOptions($state);
        $maximum = $this->maximumAnswers($state);
        $canonicalAnswers = $this->validateAnswers($answers, $options, $maximum);

        $this->recordHistory($state, 'answer', $canonicalAnswers);
        $state['selected_answers'] = [];

        if ((int) $state['stage'] === 1) {
            $this->applyStage1Answers($state, $canonicalAnswers);
            $this->advanceStage1($state, true);
        } else {
            $this->applyStage2Answers($state, $canonicalAnswers);
            $this->advanceStage2($state, true);
        }

        return $this->decorate($state);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function maximumAnswers(array $state): int
    {
        $config = $this->currentConfig($state);

        if ((int) $state['stage'] === 1 && (int) $state['part'] === 1 && $state['stage1_answered']['part1'] >= $config['maxQuestions']) {
            return 1;
        }

        return $config['answersPerQuestion'];
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     * @param  list<array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    private function validateAnswers(array $answers, array $options, int $maximum): array
    {
        if ($answers === []) {
            throw new InvalidArgumentException('At least one answer is required.');
        }

        if (count($answers) > $maximum) {
            throw new InvalidArgumentException("No more than $maximum answers are allowed.");
        }

        $available = [];

        foreach ($options as $option) {
            $available[(string) $option['key']] = $option;
        }

        $validated = [];
        $keys = [];

        foreach ($answers as $answer) {
            $key = is_array($answer) ? (string) ($answer['key'] ?? '') : (string) $answer;

            if ($key === '' || !isset($available[$key])) {
                throw new InvalidArgumentException('Answer is not available for the current question.');
            }

            if (isset($keys[$key])) {
                throw new InvalidArgumentException('An answer cannot be selected more than once.');
            }

            if (is_array($answer) && isset($answer['value']) && $answer['value'] !== $available[$key]['value']) {
                throw new InvalidArgumentException('Answer content does not match the current question.');
            }

            $keys[$key] = true;
            $validated[] = $available[$key];
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<array<string, mixed>>  $answers
     */
    private function recordHistory(array &$state, string $action, array $answers): void
    {
        $state['history'][] = [
            'action' => $action,
            'answers' => $answers,
            'skips_at_this_point' => $state['skips'],
            'snapshot' => $this->historySnapshot($state),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function historySnapshot(array $state): array
    {
        unset($state['history'], $state['question'], $state['options'], $state['progress'], $state['allowed_actions']);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<array<string, mixed>>  $answers
     */
    private function applyStage1Answers(array &$state, array $answers): void
    {
        $part = (int) $state['part'];
        $scores = &$state['scores']['stage1']["part$part"];

        foreach ($answers as $answer) {
            $category = (string) $answer['category'];

            if (!in_array($category, self::INSTINCTS, true)) {
                throw new InvalidArgumentException('Stage one answers must target an instinct.');
            }

            $scores[$category]++;
        }

        if ($part === 1) {
            $state['stage1_duplicate_answers'] += $this->duplicateCategoryCount($answers);
        }

        $state['stage1_answered']["part$part"]++;
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     */
    private function duplicateCategoryCount(array $answers): int
    {
        $categories = array_map(fn(array $answer): string => (string) $answer['category'], $answers);

        return count($categories) - count(array_unique($categories));
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function advanceStage1(array &$state, bool $isAnswer): void
    {
        $part = (int) $state['part'];
        $config = $this->currentConfig($state);
        $pool = $state['pools']['stage1']["part$part"];

        if ($part === 1) {
            if ($this->shouldEndStage1Part1($state, $config, $pool)) {
                $state['stage1_part1_winner'] = $this->leader($state['scores']['stage1']['part1']);
                $state['part'] = 2;
                $state['question_index'] = 0;
                $state['skips'] = 0;
                $state['stage1_extra_asked'] = false;
            } else {
                $state['question_index']++;
            }

            return;
        }

        if ($isAnswer && $this->shouldAskStage1ExtraTieBreaker($state, $config, $pool)) {
            $state['stage1_extra_asked'] = true;
            $state['question_index']++;

            return;
        }

        if ($this->shouldEndStage1Part2($state, $config, $pool)) {
            $this->completeStage1($state);

            return;
        }

        $state['question_index']++;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, int|string>  $config
     * @param  list<array<string, mixed>>  $pool
     */
    private function shouldEndStage1Part1(array $state, array $config, array $pool): bool
    {
        $scores = $state['scores']['stage1']['part1'];
        $index = (int) $state['question_index'];
        $reachedLead = $this->hasLead($scores, (int) ($config['minLead'] ?? 0));
        $reachedMax = $state['stage1_answered']['part1'] >= $config['maxQuestions'];
        $isTie = $this->isTopTwoTie($scores);

        if ($reachedMax && !$reachedLead && $isTie && !$this->isLastQuestion($index, $pool)) {
            return false;
        }

        return $reachedLead || $reachedMax || $this->isLastQuestion($index, $pool);
    }

    /**
     * @param  array<string, int>  $scores
     */
    private function hasLead(array $scores, int $threshold): bool
    {
        if ($threshold <= 0) {
            return false;
        }

        $values = array_values($scores);
        rsort($values);

        return ($values[0] ?? 0) - ($values[1] ?? 0) >= $threshold;
    }

    /**
     * @param  array<string, int>  $scores
     */
    private function isTopTwoTie(array $scores): bool
    {
        $values = array_values($scores);
        rsort($values);

        return count($values) >= 2 && $values[0] === $values[1];
    }

    /**
     * @param  list<array<string, mixed>>  $pool
     */
    private function isLastQuestion(int $index, array $pool): bool
    {
        return $index >= count($pool) - 1;
    }

    /**
     * @param  array<string, int>  $scores
     */
    private function leader(array $scores): string
    {
        arsort($scores);

        return (string) array_key_first($scores);
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, int|string>  $config
     * @param  list<array<string, mixed>>  $pool
     */
    private function shouldAskStage1ExtraTieBreaker(array $state, array $config, array $pool): bool
    {
        return $state['stage1_answered']['part2'] >= $config['maxQuestions']
            && !$state['stage1_extra_asked']
            && $this->isTopTwoTie($state['scores']['stage1']['part2'])
            && !$this->isLastQuestion((int) $state['question_index'], $pool)
            && !$this->hasLead($state['scores']['stage1']['part2'], (int) ($config['minLead'] ?? 0));
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, int|string>  $config
     * @param  list<array<string, mixed>>  $pool
     */
    private function shouldEndStage1Part2(array $state, array $config, array $pool): bool
    {
        $scores = $state['scores']['stage1']['part2'];
        $winner = $state['stage1_part1_winner'];
        $leader = $this->leader($scores);
        $sameWinner = is_string($winner) && $leader === $winner;
        $reachedMax = $state['stage1_answered']['part2'] >= $config['maxQuestions'];
        $standardLead = !$sameWinner && $this->hasLead($scores, (int) ($config['minLead'] ?? 0));
        $alternativeApplies = is_string($winner) && ($scores[$winner] ?? 0) === 0;
        $alternativeLead = $alternativeApplies && $this->hasLead($scores, (int) ($config['minLeadAlternative'] ?? 0));

        if ($reachedMax && !$standardLead && !$alternativeLead && !$this->isLastQuestion(
                (int) $state['question_index'],
                $pool,
            )) {
            return false;
        }

        return $standardLead || $alternativeLead || $reachedMax || $this->isLastQuestion(
                (int) $state['question_index'],
                $pool,
            );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function completeStage1(array &$state): void
    {
        $part1Winner = $state['stage1_part1_winner'];
        $part2Scores = $state['scores']['stage1']['part2'];
        $winnerPart2 = $this->leader($part2Scores);
        $isUnresolvable = $this->isTopTwoTie($part2Scores)
            || (is_string($part1Winner) && $winnerPart2 === $part1Winner)
            || !is_string($part1Winner);

        $result = [
            'scoresPart1' => $state['scores']['stage1']['part1'],
            'scoresPart2' => $part2Scores,
            'part1Winner' => $part1Winner,
            'isUnresolvable' => $isUnresolvable,
            'dominant' => null,
            'secondary' => null,
            'weakest' => null,
        ];

        if (!$isUnresolvable) {
            $result['dominant'] = $part1Winner;
            $result['secondary'] = $this->determineSecondary((string) $part1Winner, $part2Scores);
            $result['weakest'] = $this->remainingInstincts((string) $part1Winner, (string) $result['secondary'])[0];
            $state['stage'] = 2;
            $state['part'] = 1;
            $state['question_index'] = 0;
            $state['skips'] = 0;
        } else {
            $state['status'] = 'completed';
        }

        $state['result']['stage1'] = $result;
    }

    /**
     * @param  array<string, int>  $scores
     */
    private function determineSecondary(string $dominant, array $scores): string
    {
        $weakest = $this->leader($scores);

        return $this->remainingInstincts($dominant, $weakest)[0] ?? 'so';
    }

    /** @return list<string> */
    private function remainingInstincts(string $dominant, string $weakest): array
    {
        return array_values(
            array_filter(
                self::INSTINCTS,
                fn(string $instinct): bool => $instinct !== $dominant && $instinct !== $weakest,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<array<string, mixed>>  $answers
     */
    private function applyStage2Answers(array &$state, array $answers): void
    {
        $part = (int) $state['part'];
        $partScores = &$state['scores']['stage2']['per_part'][$part];

        foreach ($answers as $answer) {
            $category = (string) $answer['category'];

            if (!in_array($category, self::TYPES, true)) {
                throw new InvalidArgumentException('Stage two answers must target an enneagram type.');
            }

            $state['scores']['stage2']['total'][$category]++;
            $partScores[$category]++;

            if ($part === 1) {
                $state['stage2_selected']['part1'][$category] = true;
            }

            if ($part === 3) {
                $state['stage2_selected']['part3'][$category] = true;
            }
        }

        $state['stage2_bonus'][$part] += $this->duplicateCategoryCount($answers);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function advanceStage2(array &$state, bool $isAnswer): void
    {
        $part = (int) $state['part'];
        $config = $this->currentConfig($state);
        $instinct = $this->stage2Instinct($state, $part);
        $pool = $state['pools']['stage2'][$instinct];
        $state['stage2_pool_indices'][$instinct]++;

        if ($isAnswer) {
            $state['question_index']++;
        }

        $reachedMax = (int) $state['question_index'] >= $config['maxQuestions'];
        $noMoreQuestions = $state['stage2_pool_indices'][$instinct] >= count($pool);
        $canEndEarly = !$this->isStage2TieBreaker($part) && $this->hasLead(
                $state['scores']['stage2']['per_part'][$part],
                (int) ($config['minLead'] ?? 0),
            );

        if (!$reachedMax && !$noMoreQuestions && !$canEndEarly) {
            return;
        }

        if ($this->shouldContinueStage2TieBreak($state, $part, $config, $pool)) {
            return;
        }

        if ($this->moveToNextStage2Part($state)) {
            return;
        }

        $this->completeStage2($state);
    }

    private function isStage2TieBreaker(int $part): bool
    {
        return $part === 2 || $part === 4;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, int|string>  $config
     * @param  list<array<string, mixed>>  $pool
     */
    private function shouldContinueStage2TieBreak(array $state, int $part, array $config, array $pool): bool
    {
        if (!$this->isStage2TieBreaker($part)) {
            return false;
        }

        $instinct = $this->stage2Instinct($state, $part);

        return $state['stage2_pool_indices'][$instinct] < count($pool)
            && !$this->hasLead($state['scores']['stage2']['per_part'][$part], (int) ($config['minLead'] ?? 2));
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function moveToNextStage2Part(array &$state): bool
    {
        while ((int) $state['part'] < 4) {
            $state['part']++;
            $part = (int) $state['part'];

            if (!$this->shouldSkipStage2Part($state, $part)) {
                $state['question_index'] = 0;
                $state['skips'] = 0;

                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function shouldSkipStage2Part(array $state, int $part): bool
    {
        return ($part === 2 && count($state['stage2_selected']['part1']) <= 1)
            || ($part === 4 && count($state['stage2_selected']['part3']) <= 1);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function completeStage2(array &$state): void
    {
        $state['status'] = 'completed';
        $state['result']['stage2'] = [
            'typeScores' => $state['scores']['stage2']['total'],
            'scoresPerPart' => $state['scores']['stage2']['per_part'],
            'isUnresolvable' => $this->isTopTwoTie($state['scores']['stage2']['total']),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<array<string, mixed>|string>  $answers
     * @return array<string, mixed>
     */
    private function skip(array $state, array $answers): array
    {
        if ($answers !== []) {
            throw new InvalidArgumentException('A skip action cannot contain answers.');
        }

        if ((int) $state['skips'] >= $this->currentConfig($state)['maxSkips']) {
            throw new InvalidArgumentException('The skip limit for this part has been reached.');
        }

        $this->recordHistory($state, 'skip', []);
        $state['skips']++;
        $state['selected_answers'] = [];

        if ((int) $state['stage'] === 1) {
            $this->advanceStage1($state, false);
        } else {
            $this->advanceStage2($state, false);
        }

        return $this->decorate($state);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function back(array $state): array
    {
        $history = $state['history'];
        $entry = array_pop($history);

        if (!is_array($entry) || !is_array($entry['snapshot'] ?? null)) {
            throw new InvalidArgumentException('There is no previous enneagram question to restore.');
        }

        $restored = $entry['snapshot'];
        $restored['history'] = $history;
        $restored['selected_answers'] = $entry['action'] === 'answer' ? $entry['answers'] : [];

        return $this->decorate($restored);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function present(array $state): array
    {
        $state = $this->decorate($state);

        return [
            'version' => $state['version'],
            'locale' => $state['locale'],
            'status' => $state['status'],
            'stage' => $state['stage'],
            'part' => $state['part'],
            'question' => $state['question'],
            'options' => $state['options'],
            'progress' => $state['progress'],
            'allowed_actions' => $state['allowed_actions'],
            'result' => $state['result'],
        ];
    }
}
