<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\PageViewBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PageView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'visitor_id',
        'session_id',
        'viewable_type',
        'viewable_id',
        'ip_address',
        'user_agent',
        'user_agent_id',
        'fingerprint',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * @param $query
     * @return PageViewBuilder<static>
     */
    public function newEloquentBuilder($query): PageViewBuilder
    {
        return new PageViewBuilder($query);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userAgent(): BelongsTo
    {
        return $this->belongsTo(UserAgent::class);
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }
}
