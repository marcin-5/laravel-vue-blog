<?php

use App\Services\SloganSelector;

it('returns null when slogans are not a usable list', function (mixed $slogans) {
    $selector = new SloganSelector;

    expect($selector->select($slogans))->toBeNull();
})->with([
    'null' => [null],
    'string' => ['not a collection'],
    'empty array' => [[]],
]);

it('returns null when every slogan is blank', function () {
    $selector = new SloganSelector;

    expect($selector->select(['', '  ', "\t\n"]))->toBeNull();
});

it('returns the only slogan when a single usable entry is provided', function () {
    $selector = new SloganSelector;

    expect($selector->select(['  A single slogan  ']))->toBe('A single slogan');
});

it('always returns a trimmed slogan from the provided list', function () {
    $selector = new SloganSelector;
    $expected = ['First slogan', 'Second slogan'];

    foreach (range(1, 25) as $ignored) {
        expect($selector->select(['  First slogan  ', 2, null, '', "\tSecond slogan\t"]))->toBeIn($expected);
    }
});
