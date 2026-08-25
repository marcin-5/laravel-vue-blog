<?php

use Inertia\Testing\AssertableInertia as Assert;

it('enables requested debug capabilities with valid URL keys', function () {
    $domain = 'enneagram-test.osobliwy.localhost';
    config([
        'app.domain_locales' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.domains' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.debug' => true,
        'enneagram.debug_hints_key' => 'hints-secret',
        'enneagram.debug_scores_key' => 'scores-secret',
    ]);

    $response = $this->get('http://' . $domain . '/?debug_hints=hints-secret&debug_scores=scores-secret');

    $response->assertStatus(200);
    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/EnneagramTest/Index')
        ->where('debugHints', true)
        ->where('debugScores', true)
        ->where('initialLocale', 'pl')
        ->has('autoConfirmSingleDefault')
        ->has('seo')
        ->missing('testData'),
    );
});

it('does not enable debug capabilities without valid URL keys', function () {
    $domain = 'enneagram-test.osobliwy.localhost';
    config([
        'app.domain_locales' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.domains' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.debug' => true,
        'enneagram.debug_hints_key' => 'hints-secret',
        'enneagram.debug_scores_key' => 'scores-secret',
    ]);

    $response = $this->get('http://' . $domain . '/');

    $response->assertInertia(fn(Assert $page) => $page
        ->where('debugHints', false)
        ->where('debugScores', false),
    );
});

it('enables debug capabilities independently', function () {
    $domain = 'enneagram-test.osobliwy.localhost';
    config([
        'app.domain_locales' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.domains' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.debug' => true,
        'enneagram.debug_hints_key' => 'hints-secret',
        'enneagram.debug_scores_key' => 'scores-secret',
    ]);

    $this->get('http://' . $domain . '/?debug_hints=hints-secret')
        ->assertInertia(fn(Assert $page) => $page
            ->where('debugHints', true)
            ->where('debugScores', false),
        );

    $this->get('http://' . $domain . '/?debug_scores=scores-secret')
        ->assertInertia(fn(Assert $page) => $page
            ->where('debugHints', false)
            ->where('debugScores', true),
        );
});

it('does not enable debug capabilities when debug mode is disabled', function () {
    $domain = 'enneagram-test.osobliwy.localhost';
    config([
        'app.domain_locales' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.domains' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.debug' => false,
        'enneagram.debug_hints_key' => 'hints-secret',
        'enneagram.debug_scores_key' => 'scores-secret',
    ]);

    $response = $this->get('http://' . $domain . '/?debug_hints=hints-secret&debug_scores=scores-secret');

    $response->assertInertia(fn(Assert $page) => $page
        ->where('debugHints', false)
        ->where('debugScores', false),
    );
});

it('loads the enneagram test page with English locale for secondary domain', function () {
    $domain = 'enneagram-test.peculiarmatters.localhost';
    config([
        'app.domain_locales' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.domains' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
    ]);

    $response = $this->get('http://' . $domain . '/');

    $response->assertStatus(200);
    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/EnneagramTest/Index')
        ->where('initialLocale', 'en'),
    );
});
