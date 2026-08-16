<?php

beforeEach(function () {
    config([
        'app.domain_locales' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.domains' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.data_cache.enabled' => false,
    ]);
});

it('starts a standard test with a public server state', function () {
    $response = $this->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/start'), [
        'extended' => false,
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('contractVersion', '1')
        ->assertJsonPath('state.locale', 'pl')
        ->assertJsonPath('state.status', 'in_progress')
        ->assertJsonPath('state.stage', 1)
        ->assertJsonPath('state.part', 1)
        ->assertJsonPath('state.allowed_actions.answer', true);

    expect($response->json('testId'))
        ->toHaveLength(40)
        ->and($response->json('state.question.id'))->toBeString()
        ->and($response->json('state'))->not
        ->toHaveKey('scores')
        ->and($response->json('state'))->not->toHaveKey('pools');
});

it('starts the English test on the secondary domain', function () {
    $response = $this->postJson(enneagramApiUrl('enneagram-test.peculiarmatters.localhost', '/start'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('state.locale', 'en')
        ->assertJsonPath('state.status', 'in_progress');
});

it('starts the extended test with its stricter public action limits', function () {
    $response = $this->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/start'), [
        'extended' => true,
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('state.status', 'in_progress')
        ->assertJsonPath('state.allowed_actions.skip', false);
});

it('applies answers and can restore the previous question', function () {
    $start = $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/start'))
        ->assertSuccessful();
    $testId = $start->json('testId');
    $initialQuestionId = $start->json('state.question.id');
    $options = $start->json('state.options');

    $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/action'), [
            'testId' => $testId,
            'action' => 'answer',
            'answers' => [$options[0], $options[1]],
        ])->assertSuccessful()
        ->assertJsonPath('testId', $testId);

    $back = $this->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/action'), [
        'testId' => $testId,
        'action' => 'back',
    ]);

    $back
        ->assertSuccessful()
        ->assertJsonPath('state.question.id', $initialQuestionId)
        ->assertJsonPath('state.allowed_actions.back', false);
});

it('applies skip and reset actions', function () {
    $start = $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/start'))
        ->assertSuccessful();
    $testId = $start->json('testId');

    $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/action'), [
            'testId' => $testId,
            'action' => 'skip',
        ])->assertSuccessful()
        ->assertJsonPath('state.allowed_actions.back', true);

    $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/reset'), [
            'testId' => $testId,
        ])->assertSuccessful()
        ->assertJsonPath('status', 'reset');

    $this->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/action'), [
        'testId' => $testId,
        'action' => 'back',
    ])->assertConflict();
});

it('rejects forged answers and client-side scoring data', function () {
    $start = $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/start'))
        ->assertSuccessful();
    $testId = $start->json('testId');
    $option = $start->json('state.options.0');

    $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/action'), [
            'testId' => $testId,
            'action' => 'answer',
            'answers' => [
                [
                    'key' => $option['key'],
                    'value' => 'Forged value',
                    'category' => $option['category'],
                ],
            ],
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('answers');

    $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/action'), [
            'testId' => $testId,
            'action' => 'answer',
            'answers' => [
                [
                    'key' => $option['key'],
                    'value' => $option['value'],
                    'category' => $option['category'],
                    'score' => 999,
                ],
            ],
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('answers.0');
});

it('returns validation and conflict responses for invalid sessions', function () {
    $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/action'), [
            'testId' => str_repeat('x', 40),
            'action' => 'answer',
            'answers' => [],
        ])->assertUnprocessable()
        ->assertJsonValidationErrors(['answers']);

    $this
        ->postJson(enneagramApiUrl('enneagram-test.osobliwy.localhost', '/action'), [
            'testId' => str_repeat('x', 40),
            'action' => 'skip',
        ])->assertConflict()
        ->assertJsonPath('errors.testId.0', 'Enneagram test session not found.');

    $this
        ->postJson(enneagramApiUrl('osobliwy.localhost', '/start'))
        ->assertNotFound();
});

function enneagramApiUrl(string $host, string $path): string
{
    return "http://$host$path";
}
