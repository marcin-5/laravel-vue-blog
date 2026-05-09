<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

it('passes autoConfirmSingleDefault from config when enabled', function () {
    config()->set('enneagram.auto_confirm_single', true);
    // Ensure domain matches route domain group
    $host = config('enneagram.domain');

    get('http://' . $host . '/')
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('EnneagramTest/Index')
            ->has('autoConfirmSingleDefault')
            ->where('autoConfirmSingleDefault', true),
        );
});

it('passes autoConfirmSingleDefault from config when disabled', function () {
    config()->set('enneagram.auto_confirm_single', false);
    $host = config('enneagram.domain');

    get('http://' . $host . '/')
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('EnneagramTest/Index')
            ->has('autoConfirmSingleDefault')
            ->where('autoConfirmSingleDefault', false),
        );
});
