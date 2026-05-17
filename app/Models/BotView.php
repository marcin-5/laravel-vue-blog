<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\BotViewBuilder;
use Database\Factories\BotViewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BotView extends Model
{
    /** @use HasFactory<BotViewFactory> */
    use HasFactory;

    protected $fillable = ['user_agent_id', 'viewable_type', 'viewable_id', 'hits', 'last_seen_at'];

    /**
     * @param $query
     * @return BotViewBuilder<static>
     */
    public function newEloquentBuilder($query): BotViewBuilder
    {
        return new BotViewBuilder($query);
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function userAgent(): BelongsTo
    {
        return $this->belongsTo(UserAgent::class);
    }

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'hits' => 'integer',
        ];
    }
}
