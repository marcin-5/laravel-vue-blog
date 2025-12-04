<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

enum StatsRange: string
{
    case Today = 'today';
    case Week = 'week';
    case Month = 'month';
    case HalfYear = 'half_year';
    case Year = 'year';

    /** @return array{0:CarbonImmutable,1:CarbonImmutable} */
    public function bounds(): array
    {
        $to = CarbonImmutable::now();
        return match ($this) {
            self::Today => [$to->subDay(), $to],
            self::Week => [$to->subWeek(), $to],
            self::Month => [$to->subMonth(), $to],
            self::HalfYear => [$to->subMonths(6), $to],
            self::Year => [$to->subYear(), $to],
        };
    }
}
