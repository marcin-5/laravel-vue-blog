<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PageView extends Model
{
    use HasFactory;

    public mixed $post_id;
    public mixed $title;
    public mixed $views;
    protected $fillable = [
        'user_id',
        'visitor_id',
        'session_id',
        'viewable_type',
        'viewable_id',
        'ip_address',
        'user_agent',
        'fingerprint',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }
}
