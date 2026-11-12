<?php

use Inertia\Testing\AssertableInertia as Assert;

it('shares default system appearance in inertia props when no cookie is present', function () {
    $this->get('/')
        ->assertInertia(fn(Assert $page) => $page->where('appearance', 'system'));
});

it('shares dark appearance in inertia props when appearance cookie is dark', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get('/')
        ->assertInertia(fn(Assert $page) => $page->where('appearance', 'dark'));
});

it('shares light appearance in inertia props when appearance cookie is light', function () {
    $this->withUnencryptedCookie('appearance', 'light')
        ->get('/')
        ->assertInertia(fn(Assert $page) => $page->where('appearance', 'light'));
});
