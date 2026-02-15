<?php

namespace App\Services;

use App\Enums\StatsRange;
use App\Enums\StatsSort;

readonly class StatsCriteria
{
    public function __construct(
        public StatsRange $range,
        public ?int $bloggerId = null,
        public ?int $blogId = null,
        public ?int $limit = 5,
        public StatsSort $sort = StatsSort::ViewsDesc,
        public string $visitorGroupBy = 'visitor_id',
        public ?string $visitorType = 'all',
        public ?string $morphClass = null,
        public ?int $viewableId = null,
    ) {
    }
}
