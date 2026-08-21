<?php

use App\Services\Enneagram\EnneagramTestEngine;
use Random\RandomException;

test(
    /**
     * @throws RandomException
     */ 'creates a reproducible state from a seed',
    function () {
        $engine = new EnneagramTestEngine;
        $data = enneagramEngineData();

        $first = $engine->start($data, false, 12345, 'en');
        $second = $engine->start($data, false, 12345, 'en');

        expect($first)
            ->toBe($second)
            ->and($first['version'])->toBe('1')
            ->and($first['status'])->toBe('in_progress')
            ->and($first['stage'])->toBe(1)
            ->and($first['part'])->toBe(1)
            ->and($first['question']['id'])->toStartWith('di-');
    },
);

test('moves through stage one and restores the previous question on back', function () {
    $engine = new EnneagramTestEngine;
    $state = $engine->start(enneagramEngineData(), false, 12345, 'en');
    $answer = $state['options'][0];

    $next = $engine->apply($state, 'answer', [$answer]);
    $previous = $engine->apply($next, 'back');

    expect($next['stage'])
        ->toBe(1)
        ->and($next['part'])->toBe(2)
        ->and($previous['stage'])->toBe(1)
        ->and($previous['part'])->toBe(1)
        ->and($previous['scores']['stage1']['part1'])->toBe(['sp' => 0, 'so' => 0, 'sx' => 0])
        ->and($previous['allowed_actions']['back'])->toBeFalse();
});

test('exposes progress target separately from the question pool position', function () {
    $engine = new EnneagramTestEngine;
    $state = $engine->start(enneagramEngineData(), false, 12345, 'en');

    expect($state['progress']['answered'])
        ->toBe(0)
        ->and($state['progress']['target'])->toBe(1)
        ->and($state['progress']['position'])->toBe(1)
        ->and($state['progress']['poolSize'])->toBe(2)
        ->and($state['progress']['phase'])->toBe('standard')
        ->and($state['progress']['tieBreakerStartedAt'])->toBeNull()
        ->and($state['progress']['lead']['firstSecond'])->toBe(['value' => 0, 'target' => 1, 'alternativeTarget' => null])
        ->and($state['test_map'][0]['parts'])->toBe([
            ['part' => 1, 'status' => 'active'],
            ['part' => 2, 'status' => 'pending'],
        ])
        ->and($state['test_map'][1]['parts'][0])->toBe(['part' => 1, 'status' => 'pending']);
});

test('marks the progress as tie-breaker after the standard target is reached', function () {
    $data = enneagramEngineData();
    $data['config']['stages']['stage1']['part1']['answersPerQuestion'] = 2;
    $data['config']['stages']['stage1']['part1']['minLead'] = 2;

    foreach ($data['questions'] as &$question) {
        if (str_starts_with($question['id'], 'di-')) {
            $question['answerLists'] = ['sp' => 'SP answer', 'so' => 'SO answer'];
        }
    }
    unset($question);

    $engine = new EnneagramTestEngine;
    $state = $engine->start($data, false, 12345, 'en');
    $state = $engine->apply($state, 'answer', $state['options']);

    expect($state['stage'])
        ->toBe(1)
        ->and($state['part'])->toBe(1)
        ->and($state['progress']['answered'])->toBe(1)
        ->and($state['progress']['target'])->toBe(1)
        ->and($state['progress']['phase'])->toBe('tie_breaker')
        ->and($state['progress']['tieBreakerStartedAt'])->toBe(1)
        ->and($state['progress']['lead']['firstSecond'])->toBe(['value' => 0, 'target' => 2, 'alternativeTarget' => null]);
});

test('exposes the earlier stage one part two lead target when the part one winner is absent', function () {
    $data = enneagramEngineData();
    $data['config']['stages']['stage1']['part2']['minLead'] = 3;
    $data['config']['stages']['stage1']['part2']['minLeadAlternative'] = 2;

    $engine = new EnneagramTestEngine;
    $state = $engine->start($data, false, 12345, 'en');
    $state = $engine->apply($state, 'answer', [$state['options'][0]]);

    expect($state['stage1_part1_winner'])->toBe('sp')
        ->and($state['stage'])->toBe(1)
        ->and($state['part'])->toBe(2)
        ->and($state['progress']['lead']['firstSecond'])->toBe([
            'value' => 0,
            'target' => 3,
            'alternativeTarget' => 2,
        ])
        ->and($state['progress']['lead']['secondThird']['alternativeTarget'])->toBeNull();
});

test('presents stage two options with string keys and categories', function () {
    $data = enneagramEngineData();

    foreach ($data['questions'] as &$question) {
        if (str_starts_with($question['id'], 'sp-')) {
            $question['answerLists'] = ['1' => 'Type one answer'];
        }
    }
    unset($question);

    $engine = new EnneagramTestEngine;
    $state = $engine->start($data, false, 12345, 'en');

    while ($state['stage'] === 1) {
        $view = $engine->present($state);
        $state = $engine->apply($state, 'answer', array_slice($view['options'], 0, $view['answer_limit']));
    }

    expect($state['stage'])->toBe(2)
        ->and($state['options'][0]['key'])->toBeString()
        ->and($state['options'][0]['category'])->toBeString();
});

test('rejects an answer that is not available for the current question', function () {
    $engine = new EnneagramTestEngine;
    $state = $engine->start(enneagramEngineData(), false, 12345, 'en');

    expect(fn() => $engine->apply($state, 'answer', [
        [
            'key' => 'forged',
            'value' => 'Forged answer',
            'category' => 'sp',
        ],
    ]))->toThrow(InvalidArgumentException::class, 'Answer is not available');
});

test(
    /**
     * @throws RandomException
     */ 'finishes stage two and returns the server-computed result',
    function () {
        $engine = new EnneagramTestEngine;
        $state = $engine->start(enneagramEngineData(), false, 12345, 'en');

        while ($state['status'] === 'in_progress') {
            if ($state['stage'] === 1 && $state['part'] === 2) {
                $state = $engine->apply($state, 'answer', [$state['options'][0]]);
                continue;
            }

            $state = $engine->apply($state, 'answer', [$state['options'][0]]);
        }

        expect($state['stage'])
            ->toBe(2)
            ->and($state['status'])->toBe('completed')
            ->and($state['result']['stage2']['isUnresolvable'])->toBeFalse()
            ->and($state['result']['stage2']['typeScores'])->toHaveKey('1');
    },
);

function enneagramEngineData(): array
{
    $question = static fn(string $id, string $category): array => [
        'id' => $id,
        'question' => $id,
        'answerLists' => [$category => [$category . ' answer']],
    ];

    return [
        'locale' => 'en',
        'questions' => [
            $question('di-01', 'sp'),
            $question('di-02', 'sp'),
            $question('ri-01', 'so'),
            $question('ri-02', 'so'),
            $question('sp-01', '1'),
            $question('sp-02', '1'),
            $question('so-01', '2'),
            $question('so-02', '2'),
            $question('sx-01', '1'),
            $question('sx-02', '1'),
        ],
        'config' => [
            'stages' => [
                'stage1' => [
                    'part1' => ['maxQuestions' => 1, 'maxSkips' => 0, 'answersPerQuestion' => 1, 'minLead' => 1],
                    'part2' => [
                        'maxQuestions' => 1,
                        'maxSkips' => 0,
                        'answersPerQuestion' => 1,
                        'minLead' => 1,
                        'minLeadAlternative' => 1,
                    ],
                ],
                'stage2' => [
                    'part1' => ['maxQuestions' => 1, 'maxSkips' => 0, 'answersPerQuestion' => 1, 'minLead' => 1],
                    'part2' => ['maxQuestions' => 1, 'maxSkips' => 0, 'answersPerQuestion' => 1, 'minLead' => 1],
                    'part3' => ['maxQuestions' => 1, 'maxSkips' => 0, 'answersPerQuestion' => 1, 'minLead' => 1],
                    'part4' => ['maxQuestions' => 1, 'maxSkips' => 0, 'answersPerQuestion' => 1, 'minLead' => 1],
                ],
            ],
        ],
    ];
}
