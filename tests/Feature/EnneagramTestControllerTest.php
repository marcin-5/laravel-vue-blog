<?php

use Inertia\Testing\AssertableInertia as Assert;

it('loads the enneagram test page with valid data', function () {
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
    ]);
    config(['enneagram.debug' => true]);

    $response = $this->get('http://' . $domain . '/');

    $response->assertStatus(200);
    $response->assertInertia(fn(Assert $page) => $page
        ->component('EnneagramTest/Index')
        ->where('appDebug', true)
        ->where('initialLocale', 'pl')
        ->has('testData')
        ->has('testData.questions')
        ->has('testData.testConfig.stages.stage2.part3')
        ->has('testData.testConfig.stages.stage2.part4')
        ->where('testData.questions', function ($questions) {
            return collect($questions)->every(function ($question) {
                return isset($question['id']);
            });
        }),
    );
});

it('disables debug mode when configured', function () {
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
    ]);
    config(['enneagram.debug' => false]);

    $response = $this->get('http://' . $domain . '/');

    $response->assertInertia(fn(Assert $page) => $page
        ->where('appDebug', false),
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
        ->component('EnneagramTest/Index')
        ->where('initialLocale', 'en'),
    );
});
