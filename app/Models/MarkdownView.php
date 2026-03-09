<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MarkdownView extends Model
{
    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'ip_address',
        'user_agent',
        'hits',
        'last_seen_at',
    ];

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'hits' => 'integer',
        ];
    }
}
