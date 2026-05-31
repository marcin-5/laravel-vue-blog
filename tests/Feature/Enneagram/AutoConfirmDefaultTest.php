<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

it('passes autoConfirmSingleDefault from config when enabled', function () {
    config()->set('enneagram.auto_confirm_single', true);
    $domain = 'enneagram-test.osobliwy.localhost';
    config([
        'enneagram.domains' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
    ]);

    get('http://' . $domain)
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('EnneagramTest/Index')
            ->has('autoConfirmSingleDefault')
            ->where('autoConfirmSingleDefault', true),
        );
});

it('passes autoConfirmSingleDefault from config when disabled', function () {
    config()->set('enneagram.auto_confirm_single', false);
    $domain = 'enneagram-test.osobliwy.localhost';
    config([
        'enneagram.domains' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
    ]);

    get('http://' . $domain)
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('EnneagramTest/Index')
            ->has('autoConfirmSingleDefault')
            ->where('autoConfirmSingleDefault', false),
        );
});
