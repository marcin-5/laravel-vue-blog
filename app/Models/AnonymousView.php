<?php

namespace App\Models;

use Database\Factories\AnonymousViewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnonymousView extends Model
{
    /** @use HasFactory<AnonymousViewFactory> */
    use HasFactory;

    protected $fillable = ['user_agent_id', 'viewable_type', 'viewable_id', 'hits', 'last_seen_at'];

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
