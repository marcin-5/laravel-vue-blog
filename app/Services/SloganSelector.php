<?php

declare(strict_types=1);

namespace App\Services;

readonly class SloganSelector
{
    /**
     * Select a single slogan from the translated slogans list.
     * Selection happens once per request on the server, so SSR and the
     * client always render the same value.
     */
    public function select(mixed $slogans): ?string
    {
        if (!is_array($slogans)) {
            return null;
        }

        $slogans = array_filter($slogans, fn(mixed $slogan): bool => is_string($slogan))
                |> (fn(array $items) => array_map('trim', $items))
                |> (fn(array $items) => array_filter($items, fn(string $slogan): bool => $slogan !== ''))
                |> array_values(...);

        if ($slogans === []) {
            return null;
        }

        return $slogans[array_rand($slogans)];
    }
}
