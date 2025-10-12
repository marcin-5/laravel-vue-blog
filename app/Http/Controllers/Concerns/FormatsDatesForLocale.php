<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Carbon;

trait FormatsDatesForLocale
{
    /**
     * Format date based on locale.
     *
     * @param Carbon|null $date
     * @param string $type 'datetime' or 'date'
     * @return string|null
     */
    protected function formatDateForLocale(?Carbon $date, string $type = 'datetime'): ?string
    {
        if (!$date) {
            return null;
        }

        $locale = app()->getLocale();

        if ($locale === 'pl') {
            if ($type === 'datetime') {
                return $date->format('d.m.Y H:i');
            }
            return $date->format('d.m.Y');
        }

        // Default to English-like format
        if ($type === 'datetime') {
            return $date->toDayDateTimeString();
        }
        return $date->toDateString();
    }
}
