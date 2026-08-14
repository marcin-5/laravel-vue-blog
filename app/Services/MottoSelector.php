<?php

declare(strict_types=1);

namespace App\Services;

readonly class MottoSelector
{
    /**
     * Select a single motto from a list separated by double newlines.
     * Selection happens once per request on the server, so SSR and the
     * client always render the same value.
     */
    public function select(?string $mottoText): ?string
    {
        if ($mottoText === null) {
            return null;
        }

        $mottos = explode("\n\n", $mottoText)
                |> (fn($x) => array_map('trim', $x))
                |> (fn($x) => array_filter($x, fn(string $motto): bool => $motto !== ''))
                |> array_values(...);

        if ($mottos === []) {
            return null;
        }

        return $mottos[array_rand($mottos)];
    }
}
