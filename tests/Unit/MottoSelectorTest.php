<?php

use App\Services\MottoSelector;

it('returns null when there is no motto text', function (?string $mottoText) {
    $selector = new MottoSelector;

    expect($selector->select($mottoText))->toBeNull();
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace only' => ["   \n\n  \t "],
]);

it('returns the only motto when a single one is provided', function () {
    $selector = new MottoSelector;

    expect($selector->select('  First motto  '))->toBe('First motto');
});

it('always returns a trimmed motto from the provided list', function (string $mottoText, array $expected) {
    $selector = new MottoSelector;

    foreach (range(1, 25) as $ignored) {
        expect($selector->select($mottoText))->toBeIn($expected);
    }
})->with([
    'two mottos' => ["First motto\n\nSecond motto", ['First motto', 'Second motto']],
    'padded mottos' => ["  First motto  \n\n\tSecond motto\t", ['First motto', 'Second motto']],
    'blank entries in between' => ["First motto\n\n   \n\nSecond motto\n\n\n\nThird motto", ['First motto', 'Second motto', 'Third motto']],
]);
